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
$pic['request_level'] = 'pic';
if($pic['has_regist']) {

    $telgUser = TelegramUser::find($pic['telegram_user_id']);
    $telgPersUser = TelegramPersonalUser::findByUserId($pic['telegram_user_id']);

    $pic['full_name'] = $telgPersUser['nama'];
    $pic['telp'] = $telgPersUser['telp'];
    $pic['level'] = $telgUser['level'];
    $pic['nik'] = $telgPersUser['nik'];
    $pic['is_organik'] = $telgPersUser['is_organik'];
    $pic['instansi'] = $telgPersUser['instansi'];
    $pic['unit'] = $telgPersUser['unit'];
    $pic['regional_id'] = $telgUser['regional_id'];
    $pic['witel_id'] = $telgUser['witel_id'];

}

$admins = TelegramAdmin::getByUserArea($pic, 'request_level');
if(count($admins) < 1) return;

$request = static::request('Registration/SelectAdminPicApproval');
$request->setRegistrationData($pic);

$regional = Regional::find($pic['regional_id']);
$request->setRegional($regional);

$witel = Witel::find($pic['witel_id']);
$request->setWitel($witel);

$locations = RtuLocation::getByIds($pic['locations']);
$request->setLocations($locations);

$callbackData = new CallbackData('admin.picaprv');
$request->setInKeyboard(function($inlineKeyboardData) use ($registId, $callbackData) {
    // $inlineKeyboardData['approve']['callback_data'] = 'admin.picaprv.approve:'.$registId;
    // $inlineKeyboardData['reject']['callback_data'] = 'admin.picaprv.reject:'.$registId;
    $inlineKeyboardData['approve']['callback_data'] = $callbackData->createEncodedData([
        'i' => $registId, 'a' => 1
    ]);
    $inlineKeyboardData['reject']['callback_data'] = $callbackData->createEncodedData([
        'i' => $registId, 'a' => 0
    ]);
    return $inlineKeyboardData;
});

foreach($admins as $admin) {
    if($admin['id'] == 12) {
        $request->params->chatId = $admin['chat_id'];
        $response = $request->send();
    }
}