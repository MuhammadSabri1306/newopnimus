<?php

use App\Core\CallbackData;

$fromId = static::getFrom()->getId();

$request = static::request('Registration/SelectLevel');
$request->setTarget( static::getRequestTarget() );

$callbackData = new CallbackData('user.lvl');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem) use ($callbackData) {
    $inKeyboardItem['nasional']['callback_data'] = $callbackData->createEncodedData('nasional');
    $inKeyboardItem['regional']['callback_data'] = $callbackData->createEncodedData('regional');
    $inKeyboardItem['witel']['callback_data'] = $callbackData->createEncodedData('witel');
    return $inKeyboardItem;
});

return $request->send();