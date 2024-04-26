<?php

use App\Core\CallbackData;
use App\ApiRequest\NewosaseApiV2;
use App\Libraries\HttpClient\Exceptions\ClientException;
use App\Libraries\HttpClient\Exceptions\DataNotFoundException;
use MuhammadSabri1306\MyBotLogger\Entities\HttpClientLogger;

if(!isset($locId)) {
    throw new \Error('Undefined variable $locId');
}

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
    'location' => $locId,
];

$request = static::request('Action/Typing');
$request->setTarget( static::getRequestTarget() );
$request->send();

$rtus = [];
try {

    $osaseData = $newosaseApi->sendRequest('GET', '/parameter-service/mapview');
    $rtuData = $osaseData->find('result.0.witel.0.rtu', NewosaseApiV2::EXPECT_ARRAY_NOT_EMPTY);
    $rtus = array_map(function($item) {
        return [ 'sname' => $item->rtu_sname, 'id' => $item->id_rtu ];
    }, $rtuData);

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
$request->setRtus($rtus);

$callbackData = new CallbackData('rtu.cekrtu');
$callbackData->limitAccess($fromId);
$request->setInKeyboard(function($inKeyboardItem, $rtu) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($rtu['id']);
    return $inKeyboardItem;
});

return $request->send();