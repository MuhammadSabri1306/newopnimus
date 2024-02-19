<?php

use App\Core\CallbackData;
use App\Model\RtuLocation;

$message = static::getMessage();
$fromId = static::getFrom()->getId();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

$request = static::request('Area/SelectLocation');
$request->setTarget( static::getRequestTarget() );
$request->setLocations( RtuLocation::getSnameOrderedByWitel($witelId) );

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