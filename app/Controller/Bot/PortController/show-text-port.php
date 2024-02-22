<?php

use App\ApiRequest\NewosaseApiV2;
use App\Libraries\HttpClient\Exceptions\ClientException;
use MuhammadSabri1306\MyBotLogger\Entities\HttpClientLogger;

if(!is_array($portParams)) {
    throw new \Error(
        'newosase port parameters expecting array, \''.
        json_encode($portParams, JSON_INVALID_UTF8_IGNORE).
        '\' given'
    );
} elseif(empty($portParams)) {
    throw new \Error('newosase port parameters was empty');
}

$newosaseApi = new NewosaseApiV2();
$newosaseApi->setupAuth();
$requestUrlPath = '/dashboard-service/dashboard/rtu/port-sensors';
$newosaseApi->request['query'] = [ 'searchRtuSname' => $rtuSname ];
if(isset($portParams['port_no'])) $newosaseApi->request['query']['searchNoPort'] = $portParams['port_no'];

$request = static::request('Action/Typing');
$request->setTarget( static::getRequestTarget() );
$request->send();

$port = null;
try {

    $osaseData = $newosaseApi->sendRequest('GET', $requestUrlPath);
    $osasePortData = $osaseData->get('result.payload');
    for($i=0; $i<count($osasePortData); $i++) {

        $isPortMatch = isset($portParams['port_id']) && $portParams['port_id'] == $osasePortData[$i]->id;
        if(!$isPortMatch && isset($portParams['port_no'])) {
            $isPortMatch = $portParams['port_no'] == $osasePortData[$i]->no_port;
        }

        if($isPortMatch) {
            $port = $osasePortData[$i];
            $i = count($osasePortData);
        }

    }

} catch(ClientException $err) {

    $errResponse = $err->getResponse();
    if($errResponse && $errResponse->code == 404) {

        $request = static::request('TextDefault');
        $request->setTarget( static::getRequestTarget() );
        $request->setText(fn($text) => $text->addText('Data Port tidak dapat ditemukan.'));
        $response = $request->send();

    } else {
        $request = static::request('Error/TextErrorServer');
        $request->setTarget( static::getRequestTarget() );
        $response = $request->send();
    }

    static::logError( new HttpClientLogger($err) );
    return $response;

}

if(!$port) {

    $request = static::request('TextDefault');
    $request->setTarget( static::getRequestTarget() );
    $request->setText(fn($text) => $text->addText('Data Port tidak dapat ditemukan.'));
    return $request->send();

}

$request = static::request('Port/TextDetailPort');
$request->setTarget( static::getRequestTarget() );
$request->setPort($port);
$request->send();

$request = static::request('Action/Typing');
$request->setTarget( static::getRequestTarget() );
$request->send();

$chart = static::getPortChart($port->id);
if(!$chart) {

    $request = static::request('TextDefault');
    $request->setTarget( static::getRequestTarget() );
    $request->setText(fn($text) => $text->addText('Tidak dapat menemukan grafik port.'));
    return $request->send();

}

$request = static::request('PhotoDefault');
$request->setTarget( static::getRequestTarget() );
$request->setPhoto($chart);
return $request->send();