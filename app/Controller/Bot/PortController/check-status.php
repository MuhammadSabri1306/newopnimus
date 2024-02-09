<?php

use App\Core\CallbackData;

$message = static::$command->getMessage();
$chatId = $message->getChat()->getId();
$fromId = $message->getFrom()->getId();

$request = static::request('Port/SelectStatusType');
$request->params->chatId = $chatId;

$callbackData = new CallbackData('portsts.type');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $type) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($type['key']);
    return $inKeyboardItem;
});

return $request->send();