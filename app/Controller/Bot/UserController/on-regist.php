<?php

use App\Core\CallbackData;

$message = static::getMessage();
$messageId = $message->getMessageId();
$chatId = $message->getChat()->getId();
$from = static::getFrom();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

if(!$isApproved) {

    $request = static::request('Registration/TextTouReject');
    $request->setTarget( static::getRequestTarget() );
    $response = $request->send();
    
    $conversation = static::getRegistConversation();
    if($conversation->isExists()) {
        $conversation->cancel();
    }

    return $response;

}

$conversation = static::getRegistConversation();
if(!$conversation->isExists()) {

    $conversation->create();
    $conversation->userId = $from->getId();
    $conversation->chatId = $message->getChat()->getId();
    $conversation->type = $message->getChat()->getType();

    if($conversation->type != 'private') {
        $conversation->username = $message->getChat()->getTitle();
    } else {
        $conversation->username = $from->getUsername();
        $conversation->firstName = $from->getFirstName();
        $conversation->lastName = $from->getLastName();
    }

    if($conversation->type == 'supergroup') {
        $conversation->messageThreadId = $message->getMessageThreadId() ?? null;
    }

    $conversation->commit();

}

return static::setRegistLevel();