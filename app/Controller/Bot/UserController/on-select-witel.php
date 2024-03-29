<?php

use MuhammadSabri1306\MyBotLogger\Entities\ErrorLogger;

$conversation = static::getRegistConversation(true);
if(!$conversation) {
    return static::sendEmptyResponse();
}

try {
    if($conversation->level != 'witel') {
        throw new \Error('$conversation->level is not valid');
    }
} catch(\Throwable $err) {

    $logger = new ErrorLogger($err);
    $logger->setParams([ 'level' => $conversation->level ]);
    static::logError($logger);
    return static::sendErrorMessage();

}

$conversation->witelId = $witelId;
$conversation->nextStep();
$conversation->commit();

$message = static::getMessage();
$messageId = $message->getMessageId();
$chatId = $message->getChat()->getId();
$isPrivateChat = $message->getChat()->isPrivateChat();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

$request = static::request('TextDefault');
$request->setTarget( static::getRequestTarget() );
if($isPrivateChat) {
    $request->setText(fn($text) => $text->addText('Silahkan ketikkan nama lengkap anda.'));
} else {
    $request->setText(fn($text) => $text->addText('Silahkan ketikkan deskripsi grup.'));
}
return $request->send();