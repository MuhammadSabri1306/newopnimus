<?php

use App\Core\CallbackData;
use App\Model\TelegramUser;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\RtuLocation;
use App\Model\PicLocation;
use App\Model\RtuList;

$message = static::$command->getMessage();
$chatId = $message->getChat()->getId();
$fromId = $message->getFrom()->getId();
// static::sendDebugMessage('TEST');

$telgUser = TelegramUser::findByChatId($chatId);
if(!$telgUser) {

    $request = static::request('Error/TextUserUnidentified');
    $request->params->chatId = $chatId;
    return $request->send();

}

if($telgUser['is_pic']) {

    $picLocs = PicLocation::getByUser($telgUser['id']);
    if(count($picLocs) > 1) {
        
        $request = static::request('Area/SelectLocation');
        $request->params->chatId = $chatId;
    
        $telgUserId = $telgUser['id'];
        $request->setLocations(PicLocation::getByUser($telgUserId));
    
        $callbackData = new CallbackData('portlog.loc');
        $callbackData->limitAccess($fromId);
        $request->setInKeyboard(
            function($inKeyboardItem, $picLoc) use ($callbackData) {
                $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($picLoc['location_id']);
                return $inKeyboardItem;
            }
        );
    
        return $request->send();

    }

    $request = static::request('Area/SelectRtu');
    $request->params->chatId = $chatId;
    $request->setRtus(RtuList::getSnameOrderedByLocation($picLocs[0]['location_id']));

    $callbackData = new CallbackData('portlog.rtu');
    $callbackData->limitAccess($fromId);
    $request->setInKeyboard(function($inKeyboardItem, $rtu) use ($callbackData) {
        $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($rtu['sname']);
        return $inKeyboardItem;
    });

    return $request->send();

}

if($telgUser['level'] == 'witel') {

    $request = static::request('Area/SelectLocation');
    $request->params->chatId = $chatId;

    $witelId = $telgUser['witel_id'];
    $request->setLocations(RtuLocation::getSnameOrderedByWitel($witelId));

    $callbackData = new CallbackData('portlog.loc');
    $callbackData->limitAccess($fromId);
    $request->setInKeyboard(
        function($inKeyboardItem, $loc) use ($callbackData) {
            // static::sendDebugMessage()
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
    $request->params->chatId = $chatId;

    $request->setWitels(Witel::getNameOrdered($telgUser['regional_id']));

    $callbackData = new CallbackData('portlog.wit');
    $callbackData->limitAccess($fromId);
    $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
        $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
        return $inKeyboardItem;
    });

    return $request->send();

}

$request = static::request('Area/SelectRegional');
$request->params->chatId = $chatId;

$request->setRegionals(Regional::getSnameOrdered());

$callbackData = new CallbackData('portlog.reg');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $regional) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($regional['id']);
    return $inKeyboardItem;
});

return $request->send();