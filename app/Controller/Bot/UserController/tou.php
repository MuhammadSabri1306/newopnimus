<?php

use App\Core\CallbackData;

$fromId = static::getFrom()->getId();

$request = static::request('Registration/AnimationTou');
$request->setTarget( static::getRequestTarget() );
$request->send();

$request = static::request('Registration/TextTou');
$request->setTarget( static::getRequestTarget() );
$request->send();

$request = static::request('Registration/SelectTouApproval');
$request->setTarget( static::getRequestTarget() );

$callbackData = new CallbackData('user.aggrmnt');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem) use ($callbackData) {
    $inKeyboardItem['approve']['callback_data'] = $callbackData->createEncodedData(1);
    $inKeyboardItem['reject']['callback_data'] = $callbackData->createEncodedData(0);
    return $inKeyboardItem;
});

return $request->send();