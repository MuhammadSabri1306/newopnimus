<?php

use App\Core\CallbackData;
use App\ApiRequest\NewosaseApiV2;
use App\Model\TelegramUser;
use App\Model\Regional;
use App\Model\Witel;

$message = $callbackQuery->getMessage();
$fromId = $callbackQuery->getFrom()->getId();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

$requestSelectType = static::request('Port/SelectStatusType');
$statusTypeKeys = array_map(fn($type) => $type['key'], $requestSelectType->getData('status_types', []));
if(!in_array($statusTypeKey, $statusTypeKeys)) {
    throw new \Error("statusTypeKey not caught, value:$statusTypeKey");
}

$telgUser = TelegramUser::findByChatId($chatId);
if(!$telgUser) {

    $request = static::request('Error/TextUserUnidentified');
    $request->params->chatId = $chatId;
    return $request->send();

}

if($telgUser['level'] == 'nasional') {

    $request = static::request('Area/SelectRegional');
    $request->params->chatId = $chatId;

    $request->setRegionals(Regional::getSnameOrdered());

    $callbackData = new CallbackData('portsts'.$statusTypeKey.'.reg');
    $callbackData->limitAccess($fromId);
    $request->setInKeyboard(function($inKeyboardItem, $regional) use ($callbackData) {
        $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($regional['id']);
        return $inKeyboardItem;
    });

    return $request->send();

}

if($telgUser['level'] == 'regional') {

    $request = static::request('Area/SelectWitel');
    $request->params->chatId = $chatId;

    $request->setWitels(Witel::getNameOrdered($telgUser['regional_id']));

    $callbackData = new CallbackData('portsts'.$statusTypeKey.'.wit');
    $callbackData->limitAccess($fromId);
    $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
        $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
        return $inKeyboardItem;
    });

    return $request->send();

}

$request = static::request('Action/Typing');
$request->params->chatId = $chatId;
$request->send();

$newosaseApi = new NewosaseApiV2();
$newosaseApi->setupAuth();
$newosaseApi->request['query'] = [
    'witelId' => $telgUser['witel_id']
];

$osaseData = $newosaseApi->sendRequest('GET', '/dashboard-service/dashboard/rtu/port-sensors');
$ports = $osaseData->get('result.payload');

// Tipe Catuan
if($statusTypeKey == 'a') {

    $request = static::request('Port/TextPortStatusCatuan');
    $request->params->chatId = $chatId;
    $request->setWitel( Witel::find($telgUser['witel_id']) );
    $request->setPorts($ports);
    return $request->send();

}