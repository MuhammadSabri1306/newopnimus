<?php

use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\CallbackData;
use App\ApiRequest\NewosaseApiV2;
use App\Libraries\HttpClient\Exceptions\ClientException;
use MuhammadSabri1306\MyBotLogger\Entities\HttpClientLogger;

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

$fromId = static::getFrom()->getId();
$callbackData = new CallbackData('port.port');
$callbackData->limitAccess($fromId);

$inKeyboard = []; $index = 0; $itemCount = 0;
array_push($inKeyboard, [
    [
        'text' => 'ALL PORT',
        'callback_data' => $callbackData->createEncodedData($rtuSname)
    ]
]);
$index++;

for($i=0; $i<count($ports); $i++) {

    if( !isset($inKeyboard[$index]) ) {
        array_push($inKeyboard, []);
        $itemCount = 0;
    }

    $portValue = implode('.', [ $rtuSname, $ports[$i]->id ]);
    array_push($inKeyboard[$index], [
        'text' => $ports[$i]->no_port,
        'callback_data' => $callbackData->createEncodedData($portValue)
    ]);

    $itemCount++;
    if($itemCount >= 3) $index++;

}

$maxInKeyboardCount = 30;
$inKeyboardCount = count($inKeyboard);
$useSplit = $inKeyboardCount > $maxInKeyboardCount;
$useAvgSplit = $useSplit && ($inKeyboardCount % $maxInKeyboardCount < $maxInKeyboardCount / 2);

$request = static::request('SelectDefault');
$request->setTarget( static::getRequestTarget() );
$request->setText(fn($text) => $text->addText('Silahkan pilih ')->addBold('Port')->addText('.'));
$sendedMsgIds = [];

if(!$useSplit) {

    $request->setInKeyboard($inKeyboard);
    $response = $request->send();
    if($response->isOk()) {
        array_push($sendedMsgIds, $response->getResult()->getMessageId());
    }

} else {

    $splittedCountTarget = ceil($inKeyboardCount / $maxInKeyboardCount);
    $maxInKeyboardLine = $maxInKeyboardCount;
    if($useAvgSplit) {
        $maxInKeyboardLine = (int) ceil($inKeyboardCount / $splittedCountTarget);
    }
    
    $splittedInKeyboard = [];
    $splitIndex = 0;
    $inKeyboardLine = 0;
    foreach($inKeyboard as $item) {
        if(!isset($splittedInKeyboard[$splitIndex])) {
            array_push($splittedInKeyboard, []);
        }
    
        array_push($splittedInKeyboard[$splitIndex], $item);
        $inKeyboardLine++;
    
        if($inKeyboardLine >= $maxInKeyboardLine) {
            $splitIndex++;
            $inKeyboardLine = 0;
        }
    }

    foreach($splittedInKeyboard as $inKeyboard) {
        $request->setInKeyboard($inKeyboard);
        $response = $request->send();
        if($response->isOk()) {
            array_push($sendedMsgIds, $response->getResult()->getMessageId());
        }
    }

}

if(count($sendedMsgIds) < 1) {

    $request = static::request('Error/TextErrorServer');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

$conversation = static::getCekPortAllConversation();
if(!$conversation->isExists()) $conversation->create();
$conversation->messageIds = $sendedMsgIds;
$conversation->commit();

return static::sendEmptyResponse();