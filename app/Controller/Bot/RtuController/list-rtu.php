<?php

use App\Core\CallbackData;
use App\Model\Witel;
use App\Model\Regional;

$fromId = static::getFrom()->getId();
$chatId = static::getMessage()->getChat()->getId();

$telgUser = static::getUser();
if(!$telgUser) {

    $request = static::request('Error/TextUserUnidentified');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

if($telgUser['level'] == 'witel') {
    return static::showWitelRtus($telgUser['witel_id']);
}

if($telgUser['level'] == 'regional') {

    $request = static::request('Area/SelectWitel');
    $request->setTarget( static::getRequestTarget() );
    $request->setWitels( Witel::getNameOrdered($telgUser['regional_id']) );

    $callbackData = new CallbackData('rtu.listwit');
    $callbackData->limitAccess($fromId);
    $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
        $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
        return $inKeyboardItem;
    });
    
    return $request->send();

}

$request = static::request('Area/SelectRegional');
$request->setTarget( static::getRequestTarget() );
$request->setRegionals( Regional::getSnameOrdered() );

$callbackData = new CallbackData('rtu.listreg');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
    return $inKeyboardItem;
});

return $request->send();