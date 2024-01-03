<?php

use App\Model\TelegramAdmin;
use App\Model\Registration;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\AlertUsers;
use App\Controller\Bot\UserController;

$message = $callbackQuery->getMessage();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();

list($callbackAnswer, $registId) = explode(':', $callbackData);
$regist = Registration::find($registId);

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

if(!$regist) {
    
    $request = static::request('Registration/TextNotFound');
    $request->params->chatId = $chatId;
    $request->params->messageId = $messageId;
    return $request->sendUpdate();

}

if($regist['status'] != 'unprocessed') {

    $request = static::request('Registration/TextDoneReviewed');
    $request->params->chatId = $chatId;
    $request->setStatusText( $regist['status'] == 'approved' ? 'disetujui' : 'ditolak' );
    $request->setAdminData( TelegramAdmin::find($regist['updated_by']) );
    return $request->send();

}

$admin = TelegramAdmin::findByChatId($chatId);
if($callbackAnswer != 'approve') {
    
    Registration::update($regist['id'], [ 'status' => 'rejected' ], $admin['id']);

    $request = static::request('TextDefault');
    $request->params->chatId = $chatId;
    $request->setText(fn($text) => $text->addText('Permohonan registrasi telah ditolak.'));
    $response = $request->send();

    UserController::whenRegistRejected($regist['id']);
    return $response;
    
}

Registration::update($regist['id'], [ 'status' => 'approved' ], $admin['id']);

$registUser = $regist['data'];
$dataUser = [];

$dataUser['chat_id'] = $regist['chat_id'];
$dataUser['user_id'] = $regist['user_id'];
$dataUser['username'] = $registUser['username'];
$dataUser['type'] = $registUser['type'];
$dataUser['level'] = $registUser['level'];
$dataUser['regist_id'] = $regist['id'];

if($registUser['level'] == 'regional' || $registUser['level'] == 'witel') {
    $dataUser['regional_id'] = $registUser['regional_id'];
}

if($registUser['level'] == 'witel') {
    $dataUser['witel_id'] = $registUser['witel_id'];
}

if($registUser['type'] != 'private') {
    $dataUser['group_description'] = $registUser['group_description'];
} else {
    $dataUser['first_name'] = $registUser['first_name'];
    $dataUser['last_name'] = $registUser['last_name'];
}

if($registUser['is_pic']) {
    $dataUser['alert_status'] = 1;
} elseif($registUser['type'] == 'private') {
    $dataUser['alert_status'] = 0;
} elseif($registUser['level'] == 'witel') {
    $group = TelegramUser::findAlertWitelGroup($registUser['witel_id']);
    $dataUser['alert_status'] = $group ? 0 : 1;
} elseif($registUser['level'] == 'regional') {
    $group = TelegramUser::findAlertRegionalGroup($registUser['regional_id']);
    $dataUser['alert_status'] = $group ? 0 : 1;
} elseif($registUser['level'] == 'nasional') {
    $group = TelegramUser::findAlertNasionalGroup();
    $dataUser['alert_status'] = $group ? 0 : 1;
}

$telgUser = TelegramUser::create($dataUser);
if($registUser['type'] == 'private') {

    TelegramPersonalUser::create([
        'user_id' => $telgUser['id'],
        'nama' => $registUser['full_name'],
        'telp' => $registUser['telp'],
        'instansi' => $registUser['instansi'],
        'unit' => $registUser['unit'],
        'is_organik' => $registUser['is_organik'] ? 1 : 0,
        'nik' => $registUser['nik']
    ]);

}

$useAlert = false;
if($registUser['is_pic']) {
    $useAlert = true;
} elseif($registUser['type'] != 'private' && $registUser['level'] == 'witel') {
    $useAlert = AlertUsers::findPivot($registUser['level'], $registUser['witel_id']) ? false : true;
} elseif($registUser['type'] != 'private' && $registUser['level'] == 'regional') {
    $useAlert = AlertUsers::findPivot($registUser['level'], $registUser['regional_id']) ? false : true;
} elseif($registUser['type'] != 'private' && $registUser['level'] == 'nasional') {
    $useAlert = AlertUsers::findPivot($registUser['level']) ? false : true;
}

if($useAlert) {

    $dataAlert = [];
    $dataAlert['id'] = $telgUser['id'];
    $dataAlert['mode_id'] = 1;
    $dataAlert['cron_alert_status'] = 1;
    $dataAlert['user_alert_status'] = 1;
    
    if(!$registUser['is_pic']) {
        $dataAlert['is_pivot_group'] = 1;
        $dataAlert['pivot_level'] = $registUser['level'];
        if($registUser['level'] == 'regional') {
            $dataAlert['pivot_id'] = $registUser['regional_id'];
        } elseif($registUser['level'] == 'witel') {
            $dataAlert['pivot_id'] = $registUser['witel_id'];
        }
    }
    // static::sendDebugMessage($dataAlert);

    AlertUsers::create($dataAlert);

}

$request = static::request('TextDefault');
$request->params->chatId = $chatId;
$request->setText(fn($text) => $text->addText('Akses telah diizinkan.'));
$response = $request->send();

UserController::whenRegistApproved($regist['id']);
return $response;