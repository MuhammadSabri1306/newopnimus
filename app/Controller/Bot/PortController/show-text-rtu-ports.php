<?php

use App\ApiRequest\NewosaseApiV2;
use App\Libraries\HttpClient\Exceptions\ClientException;
use MuhammadSabri1306\MyBotLogger\Entities\HttpClientLogger;
use MuhammadSabri1306\MyBotLogger\Entities\ErrorWithDataLogger;

$newosaseApi = new NewosaseApiV2();
$newosaseApi->setupAuth();
$newosaseApi->request['query'] = [ 'searchRtuSname' => $rtuSname ];
$requestUrlPath = '/dashboard-service/dashboard/rtu/port-sensors';

$request = static::request('Action/Typing');
$request->setTarget( static::getRequestTarget() );
$request->send();

$ports = [];
try {

    $osaseData = $newosaseApi->sendRequest('GET', $requestUrlPath);
    $osasePortData = $osaseData->get('result.payload');
    $ports = array_filter($osasePortData, function($port) {
        return $port->no_port != 'many';
    });

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

    static::logError(new HttpClientLogger($err));
    return $response;

}

if(count($ports) < 1) {

    $request = static::request('TextDefault');
    $request->setTarget( static::getRequestTarget() );
    $request->setText(fn($text) => $text->addText('Data Port tidak dapat ditemukan.'));
    return $request->send();

}

$request = static::request('Port/TextPortList');
$request->setTarget( static::getRequestTarget() );
$request->setPorts($ports);
return $request->send();