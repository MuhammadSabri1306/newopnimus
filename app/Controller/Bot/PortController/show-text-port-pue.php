<?php

use MuhammadSabri1306\MyBotLogger\Entities\HttpClientLogger;
use MuhammadSabri1306\MyBotLogger\Entities\ErrorLogger;
use App\Libraries\HttpClient\Exceptions\ClientException;
use App\Libraries\HttpClient\Exceptions\DataNotFoundException;
use App\Model\Regional;
use App\Model\Witel;
use App\ApiRequest\NewosaseApiV2;

$request = static::request('Action/Typing');
$request->setTarget( static::getRequestTarget() );
$request->send();

$newosaseApi = new NewosaseApiV2();
$newosaseApi->setupAuth();
$requestUrlPath = '/dashboard-service/dashboard/rtu/port-sensors';

$newosaseApi->request['query'] = [
    'searchNoPort' => 'A-92',
    'regionalId' => $regionalId
];
if($witelId) {
    $newosaseApi->request['query']['witelId'] = $witelId;
}

$request = static::request('Action/Typing');
$request->setTarget( static::getRequestTarget() );
$request->send();

$ports = [];
try {

    $osaseData = $newosaseApi->sendRequest('GET', $requestUrlPath);
    $ports = $osaseData->find('result.payload', NewosaseApiV2::EXPECT_ARRAY_NOT_EMPTY);

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

} catch (DataNotFoundException $err) {

    $request = static::request('TextDefault');
    $request->setTarget( static::getRequestTarget() );
    $request->setText(fn($text) => $text->addText('Data Port tidak dapat ditemukan.'));
    return $request->send();

}

if(!$witelId) {

    $request = static::request('Port/TextPortPueRegional');
    $request->setTarget( static::getRequestTarget() );
    $request->setRegional( Regional::find($regionalId) );
    $request->setPorts($ports);
    return $request->send();

}

$request = static::request('Port/TextPortPueWitel');
$request->setTarget( static::getRequestTarget() );
$request->setRegional( Regional::find($regionalId) );
$request->setWitel( Witel::find($witelId) );
$request->setPorts($ports);
return $request->send();