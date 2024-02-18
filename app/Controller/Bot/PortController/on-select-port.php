<?php

$chatId = static::getMessage()->getChat()->getId();

$conversation = static::getCekPortAllConversation();
if($conversation->isExists()) {
    $messageIds = $conversation->messageIds ?? [];
    $conversation->done();
    foreach($messageIds as $msgId) {
        static::request('Action/DeleteMessage', [ $msgId, $chatId ])->send();
    }
}

$dataArr = explode('.', $callbackValue);
$rtuSname = isset($dataArr[0]) ? $dataArr[0] : null;
$portId = isset($dataArr[1]) ? $dataArr[1] : null;

if(isset($rtuSname, $portId)) {
    return static::showTextPort($rtuSname, [ 'port_id' => $portId ]);
}
return static::showTextRtuPorts($rtuSname);