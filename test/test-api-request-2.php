<?php
require __DIR__.'/../app/bootstrap.php';

use App\ApiRequest\NewosaseApiV2;

$locationId = '1259';

try {

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

    dd($portList);

} catch(\Throwable $err) {
    dd($err);
}