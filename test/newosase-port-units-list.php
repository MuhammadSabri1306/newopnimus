<?php
require __DIR__.'/../app/bootstrap.php';

use App\ApiRequest\NewosaseApiV2;
use App\Model\Regional;

try {

    $ports = [];
    $newosaseApi = new NewosaseApiV2();

    $regionalId = $_GET['regionalId'] ?? 2;

    $newosaseApi->request['query'] = [];
    if(isset($_GET['regionalId'])) $newosaseApi->request['query']['regionalId'] = $_GET['regionalId'];
    if(isset($_GET['witelId'])) $newosaseApi->request['query']['witelId'] = $_GET['witelId'];
    if(isset($_GET['isAlert'])) $newosaseApi->request['query']['isAlert'] = $_GET['isAlert'];

    $osaseData = $newosaseApi->sendRequest('GET', '/dashboard-service/dashboard/rtu/port-sensors');
    $data = $osaseData->get('result.payload');
    if(is_array($data)) {
        $ports = array_merge($ports, $data);
    }

    $unitsKey = [];
    $unitsData = [];
    foreach($ports as $port) {
        
        if(!in_array($port->units, $unitsKey)) {
            array_push($unitsKey, $port->units);
            array_push($unitsData, [
                'units' => $port->units,
                'ports' => [
                    [
                        'rtu_sname' => $port->rtu_sname,
                        'port_no' => $port->no_port,
                        'port_name' => $port->port_name,
                        'port_description' => $port->description,
                        'port_identifier' => $port->identifier,
                    ]
                ]
            ]);
        } else {
            foreach($unitsData as $index => $entries) {
                if($entries['units'] == $port->units) {
                    array_push($unitsData[$index]['ports'], [
                        'rtu_sname' => $port->rtu_sname,
                        'port_no' => $port->no_port,
                        'port_name' => $port->port_name,
                        'port_description' => $port->description,
                        'port_identifier' => $port->identifier,
                    ]);
                }
            }
        }

    }

    dd_json([
        'unitsKey' => $unitsKey,
        'unitsData' => $unitsData,
    ]);

} catch(\Throwable $err) {
    dd(strval($err));
}