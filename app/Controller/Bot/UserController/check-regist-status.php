<?php

use App\Controller\Bot\AdminController;
use App\Model\Registration;
use App\Model\Regional;
use App\Model\Witel;

$chat = static::getMessage()->getChat();
$isPrivateChat = $chat->isPrivateChat();
$chatId = $chat->getId();
$from = static::getFrom();

if(!static::getUser()) {

    $regist = Registration::query(function($db, $table) use ($chatId) {
        $query = "SELECT * FROM $table WHERE request_type='user' AND status='unprocessed' AND chat_id=%i";
        $data = $db->queryFirstRow($query, $chatId);
        if(isset($data['data'])) $data['data'] = json_decode($data['data'], true);
        return $data ?? null;
    });

    if(!$regist) return null;

    if(!isset($regist['data']['approval_messages']) || empty($regist['data']['approval_messages'])) {
        AdminController::whenRegistUser( $regist['id'] );
    }

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

$request = static::request('Registration/AnimationUserExists');
$request->setTarget( static::getRequestTarget() );

if($isPrivateChat) {
    $fullName = implode(' ', array_filter([ $from->getFirstName(), $from->getLastName() ]));
} else {
    $fullName = 'Grup '.$chat->getTitle();
}
$request->setName($fullName);

return $request->send();