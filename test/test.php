<?php

require __DIR__.'/../app/bootstrap.php';
use App\ApiRequest\NewosaseApi;
use App\Controller\BotController;

$newosaseApi = new NewosaseApi();
$newosaseApi->request['query'] = [
    'isAlert' => 1,
    'witelId' => 43
];

$fetResp = $newosaseApi->sendRequest('GET', '/dashboard-service/dashboard/rtu/port-sensors');
$ports = array_filter($fetResp->result->payload, function($port) {
    return $port->no_port != 'many';
});
dd($ports);