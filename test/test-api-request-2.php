<?php
require __DIR__.'/../app/bootstrap.php';

use App\ApiRequest\NewosaseApiV2;
use App\Libraries\HttpClient\Exceptions\RequestException;

try {

    $newosaseApi = new NewosaseApiV2();
    $newosaseApi->setupAuth();
    $newosaseApi->request['query'] = [
        'locationId' => 1256,
        'limit' => 1
    ];
    
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

    dd($rtuSnames);

} catch(\Throwable $err) {
    dd($err);
}