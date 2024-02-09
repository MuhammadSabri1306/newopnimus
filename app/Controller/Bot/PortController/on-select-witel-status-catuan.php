<?php

use App\ApiRequest\NewosaseApiV2;
use App\Model\Witel;

$message = $callbackQuery->getMessage();
$fromId = $callbackQuery->getFrom()->getId();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();
$request = static::request('Action/Typing');
$request->params->chatId = $chatId;
$request->send();

$newosaseApi = new NewosaseApiV2();
$newosaseApi->setupAuth();
$newosaseApi->request['query'] = [
    'witelId' => $witelId
];

$osaseData = $newosaseApi->sendRequest('GET', '/dashboard-service/dashboard/rtu/port-sensors');
$ports = $osaseData->get('result.payload');

$request = static::request('Port/TextPortStatusCatuan');
$request->params->chatId = $chatId;
$request->setWitel( Witel::find($witelId) );
$request->setPorts($ports);
return $request->send();