<?php

use App\Core\CallbackData;
use App\ApiRequest\NewosaseApiV2;
use App\Libraries\HttpClient\Exceptions\ClientException;
use App\Libraries\HttpClient\Exceptions\DataNotFoundException;
use MuhammadSabri1306\MyBotLogger\Entities\HttpClientLogger;
use MuhammadSabri1306\MyBotLogger\Entities\ErrorLogger;
use App\Model\Witel;
use App\Model\AlarmHistory;

$message = static::getMessage();
$fromId = static::getFrom()->getId();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

if(is_string($locId) && substr($locId, 0, 1) == 'w') {

    $request = static::request('Port/TextLogTable');
    $request->setTarget( static::getRequestTarget() );
    $request->setLevel('witel');

    $witelId = (int) substr($locId, 1);
    $witel = Witel::find($witelId);
    $request->setWitelName( $witel['witel_name'] ?? null );

    $alarms = AlarmHistory::getCurrDayByWitelDesc($witelId);
    $request->setAlarmPorts($alarms);

    return $request->send();

}

$newosaseApi = new NewosaseApiV2();
$newosaseApi->setupAuth();
$newosaseApiUrlPath = '/parameter-service/mapview';
$newosaseApi->request['query'] = [
    'isArea' => 'hide',
    'isChildren' => 'view',
    'location' => $locId,
];

$request = static::request('Action/Typing');
$request->setTarget( static::getRequestTarget() );
$request->send();

$rtuSnames = [];
try {

    $osaseData = $newosaseApi->sendRequest('GET', $newosaseApiUrlPath);
    $rtuData = $osaseData->find('result.0.witel.0.rtu', NewosaseApiV2::EXPECT_ARRAY_NOT_EMPTY);
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

    static::logError( new HttpClientLogger($err) );
    return $response;

} catch(DataNotFoundException $err) {

    $request = static::request('TextDefault');
    $request->setTarget( static::getRequestTarget() );
    $request->setText(fn($text) => $text->addText('Data RTU tidak dapat ditemukan.'));
    return $request->send();

}

$request = static::request('Area/SelectRtu');
$request->setTarget( static::getRequestTarget() );
$request->setRtus($rtuSnames);

$callbackData = new CallbackData('portlog.rtu');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $rtuSname) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($rtuSname);
    return $inKeyboardItem;
});

return $request->send();