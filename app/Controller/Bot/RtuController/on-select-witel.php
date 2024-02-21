<?php

use App\Core\CallbackData;
use App\Model\RtuLocation;

if(!isset($witelId)) {
    throw new \Error('Undefined variable $witelId');
}

$message = static::getMessage();
$fromId = static::getFrom()->getId();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

$request = static::request('Area/SelectLocation');
$request->setTarget( static::getRequestTarget() );
$request->setData('locations', RtuLocation::getSnameOrderedByWitel($witelId));

$callbackData = new CallbackData('rtu.cekloc');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $loc) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($loc['id']);
    return $inKeyboardItem;
});

return $request->send();