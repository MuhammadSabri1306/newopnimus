<?php

use App\Model\TelegramPersonalUser;
use App\Model\PicLocation;
use App\Model\AlertUsers;
use App\Model\TelegramUser;

$message = static::getMessage();
$messageId = $message->getMessageId();
$chatId = $message->getChat()->getId();

$request = static::request('Action/DeleteMessage', [ $messageId, $chatId ]);
$response = $request->send();

if($isApproved != 1) {
    return $response;
}

$telgUser = static::getUser();
if(!$telgUser) {
    return static::sendEmptyResponse();
}

TelegramPersonalUser::deleteByUserId($telgUser['id']);
PicLocation::deleteByUserId($telgUser['id']);
AlertUsers::deleteByUserId($telgUser['id']);
TelegramUser::delete($telgUser['id']);

$request = static::request('TextDefault');
$request->setTarget( static::getRequestTarget() );
$request->setText(function($text) {
    return $text->addText('Terimakasih User/Grup ini sudah tidak terdaftar di OPNIMUS lagi.')
        ->addText(' Anda dapat melakukan registrasi kembali untuk menggunakan bot ini lagi.');
});
return $request->send();