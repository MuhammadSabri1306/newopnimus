<?php

use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\PicLocation;
use App\Model\AlertUsers;
use App\Model\Regional;
use App\Model\Witel;

$message = static::getMessage();
$messageId = $message->getMessageId();
$chatId = $message->getChat()->getId();

$response = static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();
if(!$telgUserId) {
    return $response;
}

$prevRequest = static::request('ManagementUser/SelectRemovePicApproval');
$telgUser = TelegramUser::find($telgUserId);
$prevRequest->setTelegramUser($telgUser);
$prevRequest->setTelegramPersonalUser( TelegramPersonalUser::findByUserId($telgUserId) );
$prevRequest->setLocations( PicLocation::getByUser($telgUserId) );
if($telgUser['level'] != 'nasional') $prevRequest->setRegional( Regional::find($telgUser['regional_id']) );
if($telgUser['level'] == 'witel') $prevRequest->setWitel( Witel::find($telgUser['witel_id']) );
$prevRequestText = $prevRequest->params->text;

PicLocation::deleteByUserId($telgUserId);
AlertUsers::deleteByUserId($telgUserId);
TelegramUser::update($telgUserId, [ 'is_pic' => 0 ]);

$request = static::request('TextDefault');
$request->setTarget( static::getRequestTarget() );
$request->setText(function($text) use ($prevRequestText) {
    return $text->addText($prevRequestText)->newLine(2)
        ->addText('Status PIC telah di-reset.');
});
return $request->send();