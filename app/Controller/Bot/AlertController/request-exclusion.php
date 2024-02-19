<?php

use App\Core\CallbackData;

$telgUser = static::getUser();
if(!$telgUser) {

    $request = static::request('Error/TextUserUnidentified');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

if($telgUser['type'] == 'private') {

    $request = static::request('AlertStatus/TextExclusionNotProvided');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

$request = static::request('AlertStatus/SelectExclusionContinue');
$request->setTarget( static::getRequestTarget() );

$callbackData = new CallbackData('alert.excl');
$fromId = static::getFrom()->getId();
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboard) use ($callbackData) {
    $inKeyboard['continue']['callback_data'] = $callbackData->createEncodedData(1);
    $inKeyboard['cancel']['callback_data'] = $callbackData->createEncodedData(0);
    return $inKeyboard;
});

return $request->send();