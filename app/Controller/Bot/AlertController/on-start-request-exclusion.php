<?php

$message = static::getMessage();
$messageId = $message->getMessageId();
$messageText = $message->getText(true);
$chatId = $message->getChat()->getId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

if($isApproved != 1) {

    $request = static::request('TextDefault');
    $request->setTarget( static::getRequestTarget() );
    $request->setText(fn($text) => $text->addText('Pengajuan dibatalkan.'));
    return $request->send();

}

$conversation = static::getAlertExclusionConversation();
if(!$conversation->isExists()) {
    $conversation->create();
}

$request = static::request('TextDefault');
$request->setTarget( static::getRequestTarget() );
$request->setText(fn($text) => $text->addText('Mohon deskripsikan justifikasi untuk melakukan penambahan Alerting.'));
return $request->send();