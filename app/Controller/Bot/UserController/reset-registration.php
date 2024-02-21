<?php

use App\Core\CallbackData;
use App\Model\Regional;
use App\Model\Witel;

$fromId = static::getFrom()->getId();
if(!static::getUser()) {
    
    $request = static::request('Error/TextUserUnidentified');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

$request = static::request('Registration/SelectResetApproval');
$request->setTarget( static::getRequestTarget() );

$callbackData = new CallbackData('user.reset');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardData) use ($callbackData) {
    $inKeyboardData['yes']['callback_data'] = $callbackData->createEncodedData(1);
    $inKeyboardData['no']['callback_data'] = $callbackData->createEncodedData(0);
    return $inKeyboardData;
});

return $request->send();