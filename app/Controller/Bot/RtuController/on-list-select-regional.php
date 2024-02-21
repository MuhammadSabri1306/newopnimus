<?php

use App\Core\CallbackData;
use App\Model\Witel;

if(!isset($regionalId)) {
    throw new \Error('Undefined variable $regionalId');
}

$message = static::getMessage();
$fromId = static::getFrom()->getId();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

$request = static::request('Area/SelectWitel');
$request->setWitels( Witel::getNameOrdered($regionalId) );
$request->setTarget( static::getRequestTarget() );

$callbackData = new CallbackData('rtu.listwit');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
    return $inKeyboardItem;
});

return $request->send();