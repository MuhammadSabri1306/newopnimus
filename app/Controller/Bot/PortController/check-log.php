<?php

use App\Core\CallbackData;
use App\Model\TelegramUser;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\RtuLocation;

$fromId = static::getMessage()->getFrom()->getId();
$telgUser = static::getUser();
if(!$telgUser) {

    $request = static::request('Error/TextUserUnidentified');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

if($telgUser['level'] == 'witel') {

    $request = static::request('Area/SelectLocation');
    $request->setTarget( static::getRequestTarget() );

    $witelId = $telgUser['witel_id'];
    $request->setLocations(RtuLocation::getSnameOrderedByWitel($witelId));

    $callbackData = new CallbackData('portlog.loc');
    $callbackData->limitAccess($fromId);
    $request->setInKeyboard(
        function($inKeyboardItem, $loc) use ($callbackData) {
            $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($loc['id']);
            return $inKeyboardItem;
        },
        function($inKeyboard) use ($callbackData, $witelId) {
            array_unshift($inKeyboard, [
                [
                    'text' => 'Semua Lokasi',
                    'callback_data' => $callbackData->createEncodedData("w$witelId")
                ]
            ]);
            return $inKeyboard;
        }
    );

    return $request->send();

}

if($telgUser['level'] == 'regional') {

    $request = static::request('Area/SelectWitel');
    $request->setTarget( static::getRequestTarget() );
    $request->setWitels( Witel::getNameOrdered($telgUser['regional_id']) );

    $callbackData = new CallbackData('portlog.wit');
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

$callbackData = new CallbackData('portlog.reg');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $regional) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($regional['id']);
    return $inKeyboardItem;
});

return $request->send();