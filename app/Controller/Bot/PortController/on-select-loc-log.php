<?php

use App\Core\CallbackData;
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

$request = static::request('Area/SelectRtu');
$request->params->chatId = $chatId;
$request->setRtus(RtuList::getSnameOrderedByLocation($locId));

$callbackData = new CallbackData('portlog.rtu');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $rtu) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($rtu['sname']);
    return $inKeyboardItem;
});

return $request->send();