<?php

use App\Core\CallbackData;
use App\Model\Regional;
use App\Model\Witel;

$message = static::getMessage();
$fromId = static::getFrom()->getId();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

if(!static::getUser()) {
    $request = static::request('Error/TextUserUnidentified');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();
}

$request = static::request('Area/SelectWitel');
$request->setTarget( static::getRequestTarget() );

$regional = Regional::find($regionalId);
$witels = Witel::getNameOrdered($regionalId);
$allWitelOption = [ 'id' => 'r'.$regional['id'], 'witel_name' => $regional['name'] ];
$request->setWitels([ $allWitelOption, ...$witels ]);

$callbackData = new CallbackData('portpue.wit');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
    return $inKeyboardItem;
});

return $request->send();