<?php

use App\Model\TelegramUser;
use App\Model\PicLocation;
use App\Model\AlertUsers;

$message = $callbackQuery->getMessage();
$fromId = $callbackQuery->getFrom()->getId();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();

$telgUser = TelegramUser::findByChatId($chatId);
if(!$telgUser) {
    throw new \Error('Cannot found TelegramUser by it\'s chat id, $chatId:'.$chatId);
}

$response = static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();
if($callbackValue != 'continue') {
    return $response;
}

PicLocation::deleteByUserId($telgUser['id']);
AlertUsers::deleteByUserId($telgUser['id']);
TelegramUser::update($telgUser['id'], [ 'is_pic' => 0 ]);

$request = static::request('TextDefault');
$request->params->chatId = $chatId;
$request->setText(fn($text) => $text->addText('Status PIC anda telah di-reset.'));
return $request->send();