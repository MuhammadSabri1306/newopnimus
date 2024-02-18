<?php

use App\Core\CallbackData;
use App\ApiRequest\NewosaseApiV2;
use App\Libraries\HttpClient\Exceptions\ClientException;
use MuhammadSabri1306\MyBotLogger\Entities\HttpClientLogger;

$message = static::getMessage();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();
$fromId = static::getFrom()->getId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

$newosaseApi = new NewosaseApiV2();
$newosaseApi->setupAuth();
$newosaseApi->request['query'] = [
    'isArea' => 'hide',
    'isChildren' => 'view',
    'location' => $locationId,
];

$rtuSnames = [];
try {

    $osaseData = $newosaseApi->sendRequest('GET', '/parameter-service/mapview');
    $rtuData = $osaseData->get('result.0.witel.0.rtu');
    $rtuSnames = array_reduce($rtuData, function($list, $port) {
        if(isset($port->rtu_sname) && !in_array($port->rtu_sname, $list)) {
            array_push($list, $port->rtu_sname);
        }
        return $list;
    }, []);

} catch(ClientException $err) {

    $errResponse = $err->getResponse();
    if($errResponse && $errResponse->code == 404) {

        $request = static::request('TextDefault');
        $request->setTarget( static::getRequestTarget() );
        $request->setText(fn($text) => $text->addText('Data RTU tidak dapat ditemukan.'));
        $response = $request->send();

    } else {
        $request = static::request('Error/TextErrorServer');
        $request->setTarget( static::getRequestTarget() );
        $response = $request->send();
    }


    HttpClientLogger::catch($err);
    return $response;

}

$request = static::request('Area/SelectRtu');
$request->setTarget( static::getRequestTarget() );
$request->setRtus($rtuSnames);

$callbackData = new CallbackData('port.rtu');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $rtuSname) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($rtuSname);
    return $inKeyboardItem;
});

return $request->send();