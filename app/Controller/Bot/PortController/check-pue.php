<?php

use App\Core\CallbackData;
use App\Model\TelegramUser;
use App\Model\Regional;
use App\Model\Witel;

$fromId = static::getMessage()->getFrom()->getId();
$telgUser = static::getUser();
if(!$telgUser) {

    $request = static::request('Error/TextUserUnidentified');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

if($telgUser['level'] == 'witel') {

    return static::showTextPortPue($telgUser['regional_id'], $telgUser['witel_id']);

}

if($telgUser['level'] == 'regional') {

    $request = static::request('Area/SelectWitel');
    $request->setTarget( static::getRequestTarget() );

    $regional = Regional::find($telgUser['regional_id']);
    $witels = Witel::getNameOrdered($telgUser['regional_id']);
    $allWitelOption = [ 'id' => 'r'.$regional['id'], 'witel_name' => $regional['name'] ];
    $request->setWitels([ $allWitelOption, ...$witels ]);

    $callbackData = new CallbackData('portpue.wit');
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

$callbackData = new CallbackData('portpue.reg');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $regional) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($regional['id']);
    return $inKeyboardItem;
});

return $request->send();