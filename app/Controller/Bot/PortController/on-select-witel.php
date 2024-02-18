<?php

use App\Core\CallbackData;
use App\Model\RtuLocation;

$message = static::getMessage();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();
$fromId = static::getFrom()->getId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

$request = static::request('Area/SelectLocation');
$request->setTarget( static::getRequestTarget() );
$request->setLocations( RtuLocation::getSnameOrderedByWitel($witelId) );

$callbackData = new CallbackData('port.loc');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $loc) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($loc['id']);
    return $inKeyboardItem;
});

return $request->send();