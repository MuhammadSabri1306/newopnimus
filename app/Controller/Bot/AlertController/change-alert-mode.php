<?php

use App\Core\CallbackData;
use App\Model\AlertModes;
use App\Model\AlertUsers;

$chatId = static::getMessage()->getChat()->getId();
$fromId = static::getFrom()->getId();

if(!static::getUser()) {

    $request = static::request('Error/TextUserUnidentified');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

AlertUsers::useDefaultJoinPattern();
$isUserExists = AlertUsers::chatIdExists($chatId);
if(!$isUserExists) {

    $request = static::request('TextDefault');
    $request->setTarget( static::getRequestTarget() );
    $request->setText(fn($text) => $text->addText('Anda tidak terdaftar sebagai Grup/PIC pengguna alerting'));
    return $request->send();

}

$request = static::request('AlertMode/SelectModes');
$request->setTarget( static::getRequestTarget() );
$request->setAlertModes( AlertModes::getAll() );

$callbackData = new CallbackData('alert.mode');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $mode) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($mode['id']);
    return $inKeyboardItem;
});

return $request->send();