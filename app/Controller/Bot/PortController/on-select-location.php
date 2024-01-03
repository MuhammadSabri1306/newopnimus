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
$newosaseApi->request['query'] = [ 'locationId' => $locationId ];

$osaseData = $newosaseApi->sendRequest('GET', '/dashboard-service/dashboard/rtu/port-sensors');
if(!$osaseData->get()) {
    $request = static::request('Error/TextErrorServer');
    $request->params->chatId = $chatId;
    return $request->send();
}

$portList = $osaseData->get('result.payload');
if(!is_array($portList)) {
    $request = static::request('Error/TextErrorNotFound');
    $request->params->chatId = $chatId;
    return $request->send();
}

$rtuSnames = array_reduce($portList, function($list, $port) {
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

$request = static::request('Error/TextErrorMaintenance');
$request->params->chatId = $chatId;
return $request->send();