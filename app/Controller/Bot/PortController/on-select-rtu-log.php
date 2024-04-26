<?php

use App\Model\Witel;
use App\Model\RtuLocation;
use App\Model\RtuList;
use App\Model\AlarmHistory;

$message = static::getMessage();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

if($rtu = RtuList::findBySname($rtuSname)) {
    $loc = RtuLocation::findByRtu($rtu['sname']);
    $witel = Witel::find($rtu['witel_id']);
}

$alarmPorts = AlarmHistory::getCurrDayByRtuDesc($rtuSname);

$request = static::request('Port/TextLogTable');
$request->setTarget( static::getRequestTarget() );
$request->setLevel('rtu');
$request->setRtuSname($rtuSname);
$request->setLocationName( isset($loc['location_name']) ? $loc['location_name'] : null );
$request->setWitelName( isset($witel['witel_name']) ? $witel['witel_name'] : null );
$request->setAlarmPorts($alarmPorts);
return $request->send();