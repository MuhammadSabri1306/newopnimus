<?php

use MuhammadSabri1306\MyBotLogger\Entities\ErrorWithDataLogger;
use App\Core\CallbackData;
use App\Model\Regional;

try {
    if(!in_array($level, ['nasional', 'regional', 'witel'])) {
        throw new \Error('$level is not valid');
    }
} catch(\Throwable $err) {

    ErrorWithDataLogger::catch($err, [ 'level' => $level ]);

    $request = static::request('Error/TextErrorServer');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

$conversation = static::getRegistConversation(true);
if(!$conversation) {
    return static::sendEmptyResponse();
}

$conversation->level = $level;
$conversation->commit();

$message = static::getMessage();
$fromId = static::getFrom()->getId();
$messageId = $message->getMessageId();
$chatId = $message->getChat()->getId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

if($conversation->level == 'nasional') {

    $conversation->nextStep();
    $conversation->commit();

    $request = static::request('TextDefault');
    $request->setTarget( static::getRequestTarget() );
    if($message->getChat()->isPrivateChat()) {
        $request->setText(fn($text) => $text->addText('Silahkan ketikkan nama lengkap anda.'));
    } else {
        $request->setText(fn($text) => $text->addText('Silahkan ketikkan deskripsi grup.'));
    }
    return $request->send();

}

$request = static::request('Area/SelectRegional');
$request->setTarget( static::getRequestTarget() );
$request->setRegionals( Regional::getSnameOrdered() );
$request->params->text = $request->getText()
    ->clear()
    ->addText('Silahkan pilih')
    ->addBold(' Regional ')
    ->addText('yang akan dimonitor.')
    ->get();

$callbackData = new CallbackData('user.treg');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $regional) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($regional['id']);
    return $inKeyboardItem;
});

return $request->send();