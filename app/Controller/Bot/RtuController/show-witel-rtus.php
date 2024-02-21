<?php

use MuhammadSabri1306\MyBotLogger\Entities\HttpClientLogger;
use MuhammadSabri1306\MyBotLogger\Entities\ErrorWithDataLogger;
use App\ApiRequest\NewosaseApiV2;
use App\Libraries\HttpClient\Exceptions\ClientException;
use App\Model\Regional;
use App\Model\Witel;

$newosaseApi = new NewosaseApiV2();
$newosaseApi->setupAuth();
$requestUrlPath = '/parameter-service/mapview';
$newosaseApi->request['query'] = [
    'isChildren' => 'view',
    'isArea' => 'hide',
    'level' => 2,
    'witel' => $witelId,
];

$request = static::request('Action/Typing');
$request->setTarget( static::getRequestTarget() );
$request->send();

$witelData = [];
try {

    $osaseData = $newosaseApi->sendRequest('GET', $requestUrlPath);
    $witelData = $osaseData->get('result');
    if(!is_array($witelData)) throw new \Error('rtu list is not array');
    if(count($witelData) < 1) throw new \Error('rtu list is empty');

} catch(ClientException $err) {

    $errResponse = $err->getResponse();
    if($errResponse && $errResponse->code == 404) {

        $request = static::request('TextErrorNotFound');
        $request->setTarget( static::getRequestTarget() );
        $response = $request->send();

    } else {
        $request = static::request('Error/TextErrorServer');
        $request->setTarget( static::getRequestTarget() );
        $response = $request->send();
    }


    HttpClientLogger::catch($err);
    return $response;

} catch(\Throwable $err) {

    ErrorWithDataLogger::catch($err, [
        'newosaseUrlPath' => $requestUrlPath,
        'requestUrlParams' => $newosaseApi->request['query']
    ]);

    $request = static::request('Error/TextErrorServer');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

$request = static::request('CheckRtu/TextWitelsRtuList');
$request->setTarget( static::getRequestTarget() );

$witel = Witel::find($witelId);
$regional = Regional::find($witel['regional_id']);
$request->setWitelName($witel['witel_name'], $regional['name']);
$request->setRtuOfWitel($witelData);

return $request->send();