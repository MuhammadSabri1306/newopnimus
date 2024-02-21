<?php

use App\Controller\Bot\AdminController;
use App\Model\Registration;
use App\Model\Regional;
use App\Model\Witel;

$conversation = static::getRegistConversation(true);
if(!$conversation) {
    return static::sendEmptyResponse();
}

$chatId = $conversation->chatId;
$regist = Registration::query(function($db, $table) use ($chatId) {
    $query = "SELECT * FROM $table WHERE request_type='user' AND status='unprocessed' AND chat_id=%i";
    $data = $db->queryFirstRow($query, $chatId);
    if(isset($data['data'])) $data['data'] = json_decode($data['data'], true);
    return $data ?? null;
});

if($regist) {

    $request = static::request('Registration/TextOnReview');
    $request->setTarget( static::getRequestTarget() );
    $request->setRegistration($regist);
    if($regist['data']['level'] == 'regional' || $regist['data']['level'] == 'witel') {
        $request->setRegional( Regional::find($regist['data']['regional_id']) );
    }
    if($regist['data']['level'] == 'witel') {
        $request->setWitel( Witel::find($regist['data']['witel_id']) );
    }

    return $request->send();

}

$registData = [];
$registData['request_type'] = 'user';
$registData['chat_id'] = $chatId;
$registData['user_id'] = $conversation->userId;
$registData['data']['username'] = $conversation->username;
$registData['data']['type'] = $conversation->type;
$registData['data']['is_pic'] = 0;
$registData['data']['level'] = $conversation->level;

if($conversation->level == 'regional' || $conversation->level == 'witel') {
    $registData['data']['regional_id'] = $conversation->regionalId;
}

if($conversation->level == 'witel') {
    $registData['data']['witel_id'] = $conversation->witelId;
}

if($conversation->type != 'private') {
    $registData['data']['group_description'] = $conversation->groupDescription;
} else {
    $registData['data']['first_name'] = $conversation->firstName;
    $registData['data']['last_name'] = $conversation->lastName;
    $registData['data']['full_name'] = $conversation->fullName;
    $registData['data']['telp'] = $conversation->telp;
    $registData['data']['instansi'] = $conversation->instansi;
    $registData['data']['unit'] = $conversation->unit;
    $registData['data']['is_organik'] = $conversation->isOrganik;
    $registData['data']['nik'] = $conversation->nik;
}

if($conversation->type == 'supergroup') {
    $registData['data']['message_thread_id'] = $conversation->messageThreadId;
}

$regist = Registration::create($registData);
if(!$regist) {

    $request = static::request('TextDefault');
    $request->setTarget( static::getRequestTarget() );
    $request->setText(fn($text) => $text->addText('Terdapat error saat akan menyimpan data anda. Silahkan coba beberapa saat lagi.'));
    return $request->send();

}

$request = static::request('Registration/TextOnReview');
$request->setTarget( static::getRequestTarget() );
$request->setRegistration($regist);
if($conversation->level == 'regional' || $conversation->level == 'witel') {
    $request->setRegional( Regional::find($conversation->regionalId) );
}
if($conversation->level == 'witel') {
    $request->setWitel( Witel::find($conversation->witelId) );
}

$response = $request->send();
AdminController::whenRegistUser( $regist['id'] );
$conversation->done();
return $response;