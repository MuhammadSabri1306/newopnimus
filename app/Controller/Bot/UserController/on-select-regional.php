<?php

use MuhammadSabri1306\MyBotLogger\Entities\ErrorLogger;
use App\Core\CallbackData;
use App\Model\Witel;

$conversation = static::getRegistConversation(true);
if(!$conversation) {
    return static::sendEmptyResponse();
}

try {
    if(!in_array($conversation->level, ['regional', 'witel'])) {
        throw new \Error('$conversation->level is not valid');
    }
} catch(\Throwable $err) {

    $logger = new ErrorLogger($err);
    $logger->setParams([ 'level' => $conversation->level ]);
    static::logError( $logger );
    return static::sendErrorMessage();

}

$conversation->regionalId = $regionalId;
$conversation->commit();

$message = static::getMessage();
$fromId = static::getFrom()->getId();
$messageId = $message->getMessageId();
$chatId = $message->getChat()->getId();
$isPrivateChat = $message->getChat()->isPrivateChat();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

if($conversation->level == 'regional') {

    $conversation->nextStep();
    $conversation->commit();

    $request = static::request('TextDefault');
    $request->setTarget( static::getRequestTarget() );
    if($isPrivateChat) {
        $request->setText(fn($text) => $text->addText('Silahkan ketikkan nama lengkap anda.'));
    } else {
        $request->setText(fn($text) => $text->addText('Silahkan ketikkan deskripsi grup.'));
    }
    return $request->send();

}

if($conversation->level == 'witel') {

    $request = static::request('Area/SelectWitel');
    $request->setTarget( static::getRequestTarget() );
    $request->setWitels( Witel::getNameOrdered($conversation->regionalId) );

    $request->params->text = $request->getText()
        ->clear()
        ->addText('Silahkan pilih')
        ->addBold(' Witel ')
        ->addText('yang akan dimonitor.')
        ->get();

    $callbackData = new CallbackData('user.witl');
    $callbackData->limitAccess($fromId);
    $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
        $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
        return $inKeyboardItem;
    });

    return $request->send();

}