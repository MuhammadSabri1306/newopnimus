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

        if(count($ports) > 0) {

            $request = static::request('Alarm/TextPortWitel');
            $request->setTarget( static::getRequestTarget() );
            $request->setWitel( Witel::find($witelId) );
            $request->setPorts($ports);
            return $request->send();
            
        }

        $request = static::request('TextDefault');
        $request->setTarget( static::getRequestTarget() );

        $witel = Witel::find($witelId);
        $witelName = $witel ? $witel['witel_name'] : null;
        $request->setText(function($text) use ($witelName) {
            $text->addText('✅✅')->addBold('ZERO ALARM')->addText('✅✅')->newLine()
                ->addText('Saat ini tidak ada alarm');
            if($witelName) $text->addText(' di ')->addBold($witelName);
            $text->addText(' pada ')->addBold( date('Y-m-d H:i:s') )->newLine()
                ->startInlineCode()
                ->addText('Tetap Waspada dan disiplin mengawal Network Element Kita.')
                ->addText(' Semoga Network Element Kita tetap dalam kondisi prima dan terkawal.')
                ->endInlineCode()->newLine(2)
                ->addText('Ketikan /help untuk mengakses menu OPNIMUS lainnya.');
            return $text;
        });

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
            $request->setData('witels', Witel::getNameOrdered($telgUser['regional_id']));
            
            $callbackData = new CallbackData('alarm.wit');
            $request->setInKeyboard(function($inlineKeyboardItem, $witel) use ($callbackData) {
                $inlineKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
                return $inlineKeyboardItem;
            });

            return $request->send();

        }

        return static::showWitelAlarms($telgUser['witel_id']);
    }

    public static function onSelectRegional($callbackValue, $callbackQuery)
    {
        $message = static::getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

        $request = static::request('Area/SelectWitel');
        $request->setTarget( static::getRequestTarget() );
        $request->setData('witels', Witel::getNameOrdered($callbackValue));
        
        $callbackData = new CallbackData('alarm.wit');
        $request->setInKeyboard(function($inlineKeyboardItem, $witel) use ($callbackData) {
            $inlineKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
            return $inlineKeyboardItem;
        });

        return $request->send();
    }

    public static function onSelectWitel($witelId, $callbackQuery)
    {
        $message = static::getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();
        return static::showWitelAlarms($witelId);
    }
}