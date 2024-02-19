<?php

use App\Controller\Bot\AdminController;
use App\Model\AlertUsers;
use App\Model\TelegramUser;
use App\Model\Registration;

$message = static::getMessage();
$chatId = $message->getChat()->getId();

$telgUser = static::getUser();
if(!$telgUser) {

    $request = BotController::request('Error/TextUserUnidentified');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

if($telgUser['type'] == 'private') {

    $request = static::request('AlertStatus/TextExclusionNotProvided');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

$conversation = static::getAlertExclusionConversation();
$messageText = trim($message->getText(true));

$conversation->description = $messageText;
$conversation->nextStep();
$conversation->commit();
$conversation->done();

if($telgUser['level'] == 'witel') {
    $alertUsers = AlertUsers::getByLevel('witel', $telgUser['witel_id']);
} elseif($telgUser['level'] == 'regional') {
    $alertUsers = AlertUsers::getByLevel('regional', $telgUser['regional_id']);
} elseif($telgUser['level'] == 'nasional') {
    $alertUsers = AlertUsers::getByLevel('nasional');
}

$alertGroups = [];
if(is_array($alertUsers) && count($alertUsers) > 0) {
    $alertTelgUsers = TelegramUser::getByIds( array_column($alertUsers, 'id') );
    foreach($alertTelgUsers as $alertTelgUser) {
        if(!$alertTelgUser['is_pic']) {
            array_push($alertGroups, $alertTelgUser);
        }
    }
}

$regist = Registration::create([
    'request_type' => 'alert_exclusion',
    'chat_id' => $telgUser['chat_id'],
    'user_id' => $telgUser['user_id'],
    'data' => [
        'description' => $conversation->description,
        'request_group' => $telgUser,
        'alerted_groups' => $alertGroups
    ]
]);

$request = static::request('AlertStatus/TextExclusionSubmitted');
$request->setTarget( static::getRequestTarget() );
$request->setGroupName( $telgUser['username'] );
$response = $request->send();

AdminController::whenRequestAlertExclusion($regist['id']);
return $response;