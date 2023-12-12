<?php

use App\Core\CallbackData;
use App\Controller\BotController;
use App\Model\AlertModes;
use App\Model\AlertUsers;

$message = static::$command->getMessage();
$chatId = $message->getChat()->getId();
$userChatId = $message->getFrom()->getId();

AlertUsers::useDefaultJoinPattern();
$isUserExists = AlertUsers::chatIdExists($chatId);

if(!$isUserExists) {

    $request = BotController::request('Error/TextUserUnidentified');
    $request->params->chatId = $chatId;
    return $request->send();

}

$request = BotController::request('AlertMode/SelectModes');
$request->params->chatId = $chatId;
$request->setAlertModes(AlertModes::getAll());

$callbackData = new CallbackData('alert.mode');
$callbackData->limitAccess($userChatId);
$request->setInKeyboard(function($inKeyboardItem, $mode) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($mode['id']);
    return $inKeyboardItem;
});

return $request->send();

/*
ada mode 1 (Default) : Semua alerting muncul
mode 2 (Critical) : ada beberapa kondisi alert
mode 3 (power Mode): cuman blasting genset on genset off

Iyap tiap user awalnya dapat mode 1

terus nanti dia pakai command /alertmode

baru muncul inline button

Silahkan Pilih Mode Alerting:

Mode 1 (Default): Semua tipe alarm akan di blast
Mode 2 (Critical) : Blasting tegangan drop battery starter, Tegangan DC Recti Drop dan Suhu ruangan tinggi
Mode 3 (Power) : Alarm Perihal PLN OFF dan GENSET ON
*/