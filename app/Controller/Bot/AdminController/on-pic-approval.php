<?php

use App\Model\Registration;
use App\Model\TelegramAdmin;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\AlertUsers;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\PicLocation;
use App\Model\RtuLocation;
use App\Controller\Bot\PicController;

$message = $callbackQuery->getMessage();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();

$registId = $callbackData['i'];
$isApproved = boolval($callbackData['a']);
$regist = Registration::find($registId);

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

if(!$regist) {
    
    $request = static::request('Registration/TextNotFound');
    $request->params->chatId = $chatId;
    $request->params->messageId = $messageId;
    return $request->sendUpdate();

}

$pic = $regist['data'];
$pic['request_level'] = 'pic';
if($pic['has_regist']) {
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
    $pic['regional_id'] = $telgUser['regional_id'];
    $pic['witel_id'] = $telgUser['witel_id'];
}
$prevRequest = static::request('Registration/SelectAdminPicApproval');
$prevRequest->setRegistrationData($pic);
$prevRequest->setRegional( Regional::find($pic['regional_id']) );
$prevRequest->setWitel( Witel::find($pic['witel_id']) );
$prevRequest->setLocations( RtuLocation::getByIds($pic['locations']) );
$prevRequestText = $prevRequest->params->text;

if($regist['status'] != 'unprocessed') {

    $request = static::request('Registration/TextDoneReviewed');
    $request->params->chatId = $chatId;
    $request->setStatusText( $regist['status'] == 'approved' ? 'disetujui' : 'ditolak' );

    $admin = TelegramAdmin::find($regist['updated_by']);
    if($admin) {
        if($admin['level'] == 'regional') {
            $regional = Regional::find($admin['regional_id']);
            $admin['regional_name'] = $regional ? $regional['name'] : 'NULL';
        } elseif($admin['level'] == 'witel') {
            $witel = Witel::find($admin['witel_id']);
            $admin['witel_name'] = $witel ? $witel['witel_name'] : 'NULL';
        }
    }
    $request->setAdminData($admin);
    $request->params->text = $prevRequest->getText()->newLine(2)->addText($request->params->text)->get();
    return $request->send();

}

$admin = TelegramAdmin::findByChatId($chatId);
if(!$isApproved) {
    
    Registration::update($regist['id'], [ 'status' => 'rejected' ], $admin['id']);

    $request = static::request('TextDefault');
    $request->params->chatId = $chatId;
    $request->setText(fn($text) => $text->addText($prevRequestText)->newLine(2)->addText('Pengajuan PIC telah ditolak.'));
    $response = $request->send();

    PicController::whenRegistRejected($regist['id']);
    return $response;
    
}

Registration::update($regist['id'], [ 'status' => 'approved' ], $admin['id']);
if($regist['data']['has_regist']) {

    $telgUser = TelegramUser::find($regist['data']['telegram_user_id']);
    if($telgUser) {
        TelegramUser::update($telgUser['id'], [
            'is_pic' => 1,
            'pic_regist_id' => $regist['id']
        ]);
    }

} else {

    $dataUser = [];

    $dataUser['chat_id'] = $regist['chat_id'];
    $dataUser['user_id'] = $regist['user_id'];
    $dataUser['username'] = $regist['data']['username'];
    $dataUser['type'] = $regist['data']['type'];
    $dataUser['first_name'] = $regist['data']['first_name'];
    $dataUser['last_name'] = $regist['data']['last_name'];
    $dataUser['is_pic'] = 1;
    $dataUser['regist_id'] = $regist['id'];
    $dataUser['pic_regist_id'] = $regist['id'];
    $dataUser['level'] = $regist['data']['level'];

    if($regist['data']['level'] == 'regional' || $regist['data']['level'] == 'witel') {
        $dataUser['regional_id'] = $regist['data']['regional_id'];
    }

    if($regist['data']['level'] == 'witel') {
        $dataUser['witel_id'] = $regist['data']['witel_id'];
    }

    $telgUser = TelegramUser::create($dataUser);

    TelegramPersonalUser::create([
        'user_id' => $telgUser['id'],
        'nama' => $regist['data']['full_name'],
        'telp' => $regist['data']['telp'],
        'instansi' => $regist['data']['instansi'],
        'unit' => $regist['data']['unit'],
        'is_organik' => $regist['data']['is_organik'] ? 1 : 0,
        'nik' => $regist['data']['nik']
    ]);

}

if(!$telgUser) return null;

$alertUser = AlertUsers::find($telgUser['id']);
if($alertUser) {
    AlertUsers::update($alertUser['alert_user_id'], [
        'user_alert_status' => 1
    ]);
} else {
    AlertUsers::create([
        'id' => $telgUser['id'],
        'mode_id' => 1,
        'cron_alert_status' => 1,
        'user_alert_status' => 1,
        'is_pivot_group' => 0
    ]);
}


$savedLocs = PicLocation::getByUser($telgUser['id']);
$savedLocIds = array_column($savedLocs, 'location_id');
$requestLocIds = $regist['data']['locations'];

// create and update pic location
foreach($requestLocIds as $locationId) {
    if(!in_array($locationId, $savedLocIds)) {

        PicLocation::create([
            'regist_id' => $regist['id'],
            'user_id' => $telgUser['id'],
            'location_id' => $locationId,
        ]);

    } else {

        PicLocation::update($locationId, [
            'regist_id' => $regist['id']
        ]);

    }
}

foreach($savedLocs as $savedLocItem) {
    if(!in_array($savedLocItem['location_id'], $requestLocIds)) {
        PicLocation::delete($savedLocItem['id']);
    }
}

$request = static::request('TextDefault');
$request->params->chatId = $chatId;
$request->setText(fn($text) => $text->addText($prevRequestText)->newLine(2)->addText('Pengajuan PIC telah diizinkan.'));
$response = $request->send();

PicController::whenRegistApproved($regist['id']);
return $response;