<?php

use App\Core\CallbackData;
use App\ApiRequest\NewosaseApiV2;
use App\Model\Witel;
use App\Model\RtuList;
use App\Model\AlarmPortStatus;

$message = $callbackQuery->getMessage();
$fromId = $callbackQuery->getFrom()->getId();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

if(is_string($locId) && substr($locId, 0, 1) == 'w') {

    $request = static::request('Port/TextLogTable');
    $request->params->chatId = $chatId;
    $request->setLevel('witel');

    $witelId = (int) substr($locId, 1);
    $witel = Witel::find($witelId);
    $request->setWitelName( $witel['witel_name'] ?? null );

    AlarmPortStatus::useDefaultJoinPattern();
    $alarmPorts = AlarmPortStatus::getCurrDayByWitelDesc($witelId);
    $request->setAlarmPorts($alarmPorts);

    return $request->send();

}

$request = static::request('Action/Typing');
$request->params->chatId = $chatId;
$request->send();

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

$callbackData = new CallbackData('portlog.rtu');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $rtuSname) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($rtuSname);
    return $inKeyboardItem;
});

return $request->send();