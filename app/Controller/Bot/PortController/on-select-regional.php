<?php

use App\Core\CallbackData;
use App\Model\Witel;

$message = static::getMessage();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();
$fromId = static::getFrom()->getId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

$request = static::request('Area/SelectWitel');
$request->setTarget( static::getRequestTarget() );
$request->setWitels( Witel::getNameOrdered($regionalId) );

$callbackData = new CallbackData('port.wit');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
    return $inKeyboardItem;
});

return $request->send();