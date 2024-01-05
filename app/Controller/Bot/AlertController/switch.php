<?php

use App\Controller\BotController;
use App\Model\TelegramUser;
use App\Model\AlertUsers;
use App\Model\Regional;
use App\Model\Witel;

$message = static::$command->getMessage();
$chatId = $message->getChat()->getId();
$messageText = strtolower(trim($message->getText(true)));

$telgUser = TelegramUser::findByChatId($chatId);
if(!$telgUser) {

    $request = BotController::request('Error/TextUserUnidentified');
    $request->params->chatId = $chatId;
    return $request->send();

}

if($telgUser['type'] == 'private' && !$telgUser['is_pic']) {

    $request = BotController::request('AlertStatus/TextFeatureNotProvided');
    $request->params->chatId = $chatId;
    return $request->send();

}

if($messageText == 'status') {

    $alertUser = AlertUsers::find($telgUser['id']);

    $request = BotController::request('TextDefault');
    $request->params->chatId = $chatId;

    if($alertUser['user_alert_status'] == 1) {
        $request->setText(fn($text) => $text->addItalic('Status Alert saat ini sedang ON.'));
    } else {
        $request->setText(fn($text) => $text->addItalic('Status Alert saat ini sedang OFF.'));
    }

    return $request->send();

}

$alertStatus = null;
if($messageText == 'on') {
    $alertStatus = 1;
} elseif($messageText == 'off') {
    $alertStatus = 0;
}

if($alertStatus === null) {

    $request = BotController::request('AlertStatus/TextIncompatibleFormat');
    $request->params->chatId = $chatId;
    return $request->send();

} elseif($alertStatus == 1 && !$telgUser['is_pic']) {

    $alertGroup = null;
    if($telgUser['level'] == 'witel') {

        $alertUser = AlertUsers::findPivot($telgUser['level'], $telgUser['witel_id']);
        $alertGroup = $alertUser ? TelegramUser::find($alertUser['id']) : null;

    } elseif($telgUser['level'] == 'regional') {

        $alertUser = AlertUsers::findPivot($telgUser['level'], $telgUser['regional_id']);
        $alertGroup = $alertUser ? TelegramUser::find($alertUser['id']) : null;

    } elseif($telgUser['level'] == 'nasional') {
        
        $alertUser = AlertUsers::findPivot($telgUser['level']);
        $alertGroup = $alertUser ? TelegramUser::find($alertUser['id']) : null;

    }

    if($alertGroup) {

        $request = BotController::request('AlertStatus/TextAlertGroupHasExists');
        $request->params->chatId = $chatId;
        $request->setGroupTitle($alertGroup['username']);

        if($alertGroup['level'] == 'witel') {
            $witel = Witel::find($alertGroup['witel_id']);
            $request->setLevelName($witel['witel_name']);
        } elseif($alertGroup['level'] == 'regional') {
            $regional = Regional::find($alertGroup['regional_id']);
            $request->setLevelName($regional['name']);
        } elseif($alertGroup['level'] == 'nasional') {
            $request->setLevelName('NASIONAL');
        }

        $request->buildText();
        return $request->send();

    }

}

$alertUser = AlertUsers::find($telgUser['id']);
AlertUsers::update($alertUser['alert_user_id'], [
    'user_alert_status' => $alertStatus
]);

$request = BotController::request('AlertStatus/TextSwitchSuccess', [ $alertStatus ]);
$request->params->chatId = $chatId;
return $request->send();