<?php

$message = static::getMessage();
$messageId = $message->getMessageId();
$chatId = $message->getChat()->getId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

$conversation = static::getRegistConversation(true);
if(!$conversation) {
    return static::sendEmptyResponse();
}

$conversation->isOrganik = $isOrganik == 1;
$conversation->nextStep();
$conversation->commit();

$request = static::request('TextDefault');
$request->setTarget( static::getRequestTarget() );
$request->setText(fn($text) => $text->addText('Silahkan ketikkan NIK anda.'));
return $request->send();