<?php
namespace App\Controller\Bot;

use App\Core\CallbackData;
use App\ApiRequest\NewosaseApiV2;
use App\Libraries\HttpClient\Exceptions\ClientException;
use MuhammadSabri1306\MyBotLogger\Entities\HttpClientLogger;
use MuhammadSabri1306\MyBotLogger\Entities\ErrorWithDataLogger;
use App\Controller\BotController;
use App\Model\Regional;
use App\Model\Witel;

class AlarmController extends BotController
{
    public static $callbacks = [
        'alarm.reg' => 'onSelectRegional',
        'alarm.wit' => 'onSelectWitel'
    ];

    protected static function showWitelAlarms($witelId)
    {
        $newosaseApi = new NewosaseApiV2();
        $newosaseApi->setupAuth();
        $requestUrlPath = '/dashboard-service/dashboard/rtu/port-sensors';
        $newosaseApi->request['query'] = [
            'isAlert' => 1,
            'witelId' => $witelId
        ];

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


            HttpClientLogger::catch($err);
            return $response;

        }

        $request = static::request('Alarm/TextPortWitel');
        $request->setTarget( static::getRequestTarget() );
        $request->setWitel( Witel::find($witelId) );
        $request->setPorts($ports);
        return $request->send();
    }

    protected static function showRegionalAlarms($regionalId)
    {
        $newosaseApi = new NewosaseApiV2();
        $newosaseApi->setupAuth();
        $requestUrlPath = '/dashboard-service/dashboard/rtu/port-sensors';
        $newosaseApi->request['query'] = [
            'isAlert' => 1,
            'regionalId' => $regionalId
        ];

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


            HttpClientLogger::catch($err);
            return $response;

        }

        $request = static::request('Alarm/TextPortRegional');
        $request->setTarget( static::getRequestTarget() );
        $request->setRegional( Regional::find($regionalId) );
        $request->setPorts($ports);
        return $request->send();
    }

    public static function checkExistAlarmV2()
    {
        $telgUser = static::getUser();
        if(!$telgUser) {
            
            $request = static::request('Error/TextUserUnidentified');
            $request->setTarget( static::getRequestTarget() );
            return $request->send();

        }

        if($telgUser['level'] == 'nasional') {
            
            $request = static::request('Area/SelectRegional');
            $request->setTarget( static::getRequestTarget() );
            $request->setData('regionals', Regional::getSnameOrdered());

            $callbackData = new CallbackData('alarm.reg');
            $request->setInKeyboard(function($inlineKeyboardItem, $regional) use ($callbackData) {
                $inlineKeyboardItem['callback_data'] = $callbackData->createEncodedData($regional['id']);
                return $inlineKeyboardItem;
            });

            return $request->send();

        }

        if($telgUser['level'] == 'regional') {

            $request = static::request('Area/SelectWitel');
            $request->setTarget( static::getRequestTarget() );

            $witels = Witel::getNameOrdered($telgUser['regional_id']);
            array_unshift($witels, [ 'id' => 'r'.strval($telgUser['regional_id']), 'witel_name' => 'PILIH SEMUA WITEL' ]);
            $request->setData('witels', $witels);
            
            $callbackData = new CallbackData('alarm.wit');
            $request->setInKeyboard(function($inlineKeyboardItem, $witel) use ($callbackData) {
                $inlineKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
                return $inlineKeyboardItem;
            });

            return $request->send();

        }

        return static::showWitelAlarms($telgUser['witel_id']);
    }

    public static function onSelectRegional($regionalId)
    {
        $message = static::getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

        $request = static::request('Area/SelectWitel');
        $request->setTarget( static::getRequestTarget() );

        $witels = Witel::getNameOrdered($regionalId);
        array_unshift($witels, [ 'id' => 'r'.strval($regionalId), 'witel_name' => 'PILIH SEMUA WITEL' ]);
        $request->setData('witels', $witels);
        
        $callbackData = new CallbackData('alarm.wit');
        $request->setInKeyboard(function($inlineKeyboardItem, $witel) use ($callbackData) {
            $inlineKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
            return $inlineKeyboardItem;
        });

        return $request->send();
    }

    public static function onSelectWitel($witelId)
    {
        $message = static::getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

        if(is_string($witelId) && $witelId[0] == 'r') {
            $regionalId = substr($witelId, 1);
            return static::showRegionalAlarms($regionalId);
        }

        return static::showWitelAlarms($witelId);
    }
}