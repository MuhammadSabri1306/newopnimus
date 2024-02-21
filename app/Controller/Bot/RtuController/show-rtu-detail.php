<?php

use App\ApiRequest\NewosaseApiV2;
use App\Libraries\HttpClient\Exceptions\ClientException;
use MuhammadSabri1306\MyBotLogger\Entities\HttpClientLogger;
use App\Model\RtuList;
use App\Model\RtuLocation;
use App\Model\Witel;
use App\Model\Regional;

$rtuId = isset($rtu['id']) ? $rtu['id'] : null;
$rtuSname = isset($rtu['sname']) ? $rtu['sname'] : null;

if(!$rtuId && !$rtuSname) {
    throw new \Error('$rtuSname or $rtuId expected');
}

if(!$rtuId && $rtuSname) {

    $rtuParams = RtuList::findBySname($rtuSname);
    if(!$rtuParams) {

        $request = static::request('Error/TextErrorNotFound');
        $request->setTarget( static::getRequestTarget() );
        $response = $request->send();

    }

    $newosaseApi = new NewosaseApiV2();
    $newosaseApi->setupAuth();
    $requestUrlPath = '/parameter-service/mapview';
    $newosaseApi->request['query'] = [
        'isArea' => 'hide',
        'isChildren' => 'view',
        'level' => 4,
        'location' => $rtuParams['location_id'],
    ];

    $request = static::request('Action/Typing');
    $request->setTarget( static::getRequestTarget() );
    $request->send();

    $osaseData = null;
    try {

        $osaseData = $newosaseApi->sendRequest('GET', $requestUrlPath);
        $rtus = $osaseData->get('result.0.witel.0.rtu');
        if(is_array($rtus) && count($rtus) > 0) {
            for($i=0; $i<count($rtus); $i++) {
                if($rtus[$i]->rtu_sname == $rtuSname) {
                    $rtuId = $rtus[$i]->id_rtu;
                    $i = count($rtus);
                }
            }
        }

    } catch(ClientException $err) {

        $errResponse = $err->getResponse();
        if($errResponse && $errResponse->code == 404) {

            $request = static::request('Error/TextErrorNotFound');
            $request->setTarget( static::getRequestTarget() );
            $response = $request->send();

        } else {

            $request = static::request('Error/TextErrorServer');
            $request->setTarget( static::getRequestTarget() );
            $response = $request->send();

        }


        HttpClientLogger::catch($err);
        return $response;

    }

}

if(!$rtuId) {

    $request = static::request('Error/TextErrorNotFound');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

$newosaseApi = new NewosaseApiV2();
$requestUrlPath = "/dashboard-service/operation/rtu/$rtuId";
$osaseData = null;
try {

    $osaseData = $newosaseApi->sendRequest('GET', $requestUrlPath);

} catch(ClientException $err) {

    $errResponse = $err->getResponse();
    if($errResponse && $errResponse->code == 404) {

        $request = static::request('Error/TextErrorNotFound');
        $request->setTarget( static::getRequestTarget() );
        $response = $request->send();

    } else {

        $request = static::request('Error/TextErrorServer');
        $request->setTarget( static::getRequestTarget() );
        $response = $request->send();

    }


    HttpClientLogger::catch($err);
    return $response;

}

$rtuData = $osaseData->get('result');
$rtu = $rtuData ? RtuList::findBySname($rtuData->sname) : null;
if(!$rtuData || !$rtu) {

    $request = static::request('Error/TextErrorNotFound');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

$request = static::request('CheckRtu/TextRtuDetail');
$request->setTarget( static::getRequestTarget() );
$request->setRegional( Regional::find($rtu['regional_id']) );
$request->setWitel( Witel::find($rtu['witel_id']) );
$request->setLocation( RtuLocation::find($rtu['location_id']) );
$request->setRtu( $rtuData );

$response = $request->send();
$rtuLat = $osaseData->get('result.latitude');
$rtuLng = $osaseData->get('result.longitude');

if(!$response->isOk() || !$rtuLat || !$rtuLng) {
    return $response;
}

$detailMessageId = $response->getResult()->getMessageId();

$request = static::request('Attachment/MapLocation', [ $rtuLat, $rtuLng ]);
$request->setTarget( static::getRequestTarget() );
if($detailMessageId) {
    $request->params->replyToMessageId = $detailMessageId;
}
return $request->send();