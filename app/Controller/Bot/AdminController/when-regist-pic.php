<?php

use App\Core\CallbackData;
use App\Model\Registration;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\TelegramAdmin;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\RtuLocation;

$regist = Registration::find($registId);
if(!$regist) return null;

$pic = $regist['data'];
$telgUser = TelegramUser::find($pic['telegram_user_id']);
$telgPersUser = TelegramPersonalUser::findByUserId($pic['telegram_user_id']);

$pic['full_name'] = $telgPersUser['nama'];
$pic['username'] = $telgUser['username'];
$pic['user_id'] = $telgUser['user_id'];
$pic['telp'] = $telgPersUser['telp'];
$pic['level'] = $telgUser['level'];
$pic['nik'] = $telgPersUser['nik'];
$pic['is_organik'] = $telgPersUser['is_organik'];
$pic['instansi'] = $telgPersUser['instansi'];
$pic['unit'] = $telgPersUser['unit'];

if($pic['level'] == 'regional' || $pic['level'] == 'witel') {
    $pic['regional_id'] = $telgUser['regional_id'];
}

if($pic['level'] == 'witel') {
    $pic['witel_id'] = $telgUser['witel_id'];
}

$admins = TelegramAdmin::getByUserArea($pic);
if(count($admins) < 1) return;

$request = static::request('Registration/SelectAdminPicApproval');
$request->setRegistrationData($pic);
if(isset($pic['regional_id'])) $request->setRegional( Regional::find($pic['regional_id']) );
if(isset($pic['witel_id'])) $request->setWitel( Witel::find($pic['witel_id']) );
$request->setLocations( RtuLocation::getByIds($pic['locations']) );

$callbackData = new CallbackData('admin.picaprv');
$request->setInKeyboard(function($inlineKeyboardData) use ($registId, $callbackData) {
    $inlineKeyboardData['approve']['callback_data'] = $callbackData->createEncodedData([
        'i' => $registId, 'a' => 1
    ]);
    $inlineKeyboardData['reject']['callback_data'] = $callbackData->createEncodedData([
        'i' => $registId, 'a' => 0
    ]);
    return $inlineKeyboardData;
});

$apprMessages = [];
foreach($admins as $admin) {
    $request->params->chatId = $admin['chat_id'];
    $response = $request->send();
    if($response->isOk()) {
        array_push($apprMessages, [
            'chat_id' => $admin['chat_id'],
            'message_id' => $response->getResult()->getMessageId()
        ]);
    }
}

if(count($apprMessages) > 0) {
    $regist['data']['approval_messages'] = $apprMessages;
    Registration::update($registId, [
        'data' => $regist['data']
    ], null);
}