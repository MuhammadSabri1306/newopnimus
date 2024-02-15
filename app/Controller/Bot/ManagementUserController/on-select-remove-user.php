<?php

use App\Core\CallbackData;
use App\Model\Regional;
use App\Model\Witel;

return static::sendDebugMessage('WORKING');

$message = static::getMessage();
$messageId = $message->getMessageId();
$chatId = $message->getChat()->getId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();
$admin = static::getAdmin();
if(!$admin) return static::sendEmptyResponse();

if($admin['level'] == 'nasional') {

    $request = static::request('Area/SelectRegional');
    $request->params->chatId = $chatId;
    $request->setRegionals( Regional::getSnameOrdered() );

    $callbackData = new CallbackData('mngusr.rmusertreg');
    $request->setInKeyboard(function($inKeyboardItem, $regional) use ($callbackData) {
        $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($regional['id']);
        return $inKeyboardItem;
    });

    return $request->send();

}

if($admin['level'] == 'regional') {

    $request = static::request('Area/SelectWitel');
    $request->params->chatId = $chatId;
    $request->setWitels( Witel::getNameOrdered($admin['regional_id']) );

    $callbackData = new CallbackData('mngusr.rmuserwit');
    $callbackData->limitAccess($fromId);
    $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
        $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
        return $inKeyboardItem;
    });

    return $request->send();

}