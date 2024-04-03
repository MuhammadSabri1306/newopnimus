<?php

use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\PicLocation;
use App\Model\AlertUsers;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\Registration;

$message = static::getMessage();
$messageId = $message->getMessageId();
$chatId = $message->getChat()->getId();

$response = static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();
if(!$telgUserId) {
    return $response;
}

$prevRequest = static::request('ManagementUser/SelectRemoveUserApproval');
$telgUser = TelegramUser::find($telgUserId);
$prevRequest->setTelegramUser($telgUser);
if($telgUser['type'] == 'private') $prevRequest->setTelegramPersonalUser( TelegramPersonalUser::findByUserId($telgUserId) );
if($telgUser['level'] != 'nasional') $prevRequest->setRegional( Regional::find($telgUser['regional_id']) );
if($telgUser['level'] == 'witel') $prevRequest->setWitel( Witel::find($telgUser['witel_id']) );
$prevRequestText = $prevRequest->params->text;

TelegramPersonalUser::deleteByUserId($telgUserId);
PicLocation::deleteByUserId($telgUserId);
AlertUsers::deleteByUserId($telgUserId);
TelegramUser::delete($telgUserId);
Registration::query(function ($db, $table) use ($chatId) {
    return $db->delete($table, [ 'chat_id' => $chatId, 'status' => 'unprocessed' ]);
});

$request = static::request('TextDefault');
$request->setTarget( static::getRequestTarget() );
$request->setText(function($text) use ($prevRequestText) {
    return $text->addText($prevRequestText)->newLine(2)
        ->addText('User telah dihapus.');
});
return $request->send();