<?php

use App\Core\CallbackData;
use App\ApiRequest\NewosaseApiV2;
use App\Model\Witel;
use App\Model\AlarmHistory;

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

    $alarms = AlarmHistory::getCurrDayByWitelDesc($witelId);
    $request->setAlarmPorts($alarms);

    return $request->send();

}

$request = static::request('Action/Typing');
$request->params->chatId = $chatId;
$request->send();

$newosaseApi = new NewosaseApiV2();
$newosaseApi->setupAuth();
$newosaseApi->request['query'] = [
    'isArea' => 'hide',
    'isChildren' => 'view',
    'location' => $locId,
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

$callbackData = new CallbackData('portlog.rtu');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $rtuSname) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($rtuSname);
    return $inKeyboardItem;
});

return $request->send();