<?php

use App\ApiRequest\NewosaseApiV2;
use App\Core\CallbackData;

$message = $callbackQuery->getMessage();
$fromId = $callbackQuery->getFrom()->getId();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

$newosaseApi = new NewosaseApiV2();
$newosaseApi->setupAuth();
$newosaseApi->request['query'] = [
    'isArea' => 'hide',
    'isChildren' => 'view',
    'location' => $locationId,
];

$osaseData = $newosaseApi->sendRequest('GET', '/parameter-service/mapview');
$rtuData = $osaseData->get('result.0.witel.0.rtu');
if(!is_array($rtuData)) {
    $request = static::request('Error/TextErrorNotFound');
    $request->params->chatId = $chatId;
    return $request->send();
}

$rtuSnames = array_reduce($rtuData, function($list, $port) {
    if(isset($port->rtu_sname) && !in_array($port->rtu_sname, $list)) {
        array_push($list, $port->rtu_sname);
    }
    return $list;
}, []);

$request = static::request('Area/SelectRtu');
$request->params->chatId = $chatId;
$request->setRtus($rtuSnames);

$callbackData = new CallbackData('port.rtu');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $rtuSname) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($rtuSname);
    return $inKeyboardItem;
});

return $request->send();