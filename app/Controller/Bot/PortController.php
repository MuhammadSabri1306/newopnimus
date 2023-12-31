<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\ChatAction;
use Goat1000\SVGGraph\SVGGraph;

use App\Core\RequestData;
use App\Core\CallbackData;
use App\BuiltMessageText\UserText;
use App\BuiltMessageText\PortText;
use App\Controller\BotController;
use App\Model\TelegramUser;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\RtuLocation;
use App\Model\RtuList;
use App\ApiRequest\NewosaseApi;
use App\Request\RequestInKeyboard;

class PortController extends BotController
{
    public static $callbacks = [
        'port.reg' => 'onSelectRegional',
        'port.wit' => 'onSelectWitel',
        'port.loc' => 'onSelectLocation',
        'port.rtu' => 'onSelectRtu',
        'port.port' => 'onSelectPort',

        'portlog.reg' => 'onSelectRegionalLog',
        'portlog.wit' => 'onSelectWitelLog',
        'portlog.loc' => 'onSelectLocationLog',
        'portlog.rtu' => 'onSelectRtuLog',
    ];

    public static function checkPort()
    {
        $message = PortController::$command->getMessage();
        $messageText = trim($message->getText(true));
        $chatId = $message->getChat()->getId();
        $userChatId = $message->getFrom()->getId();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;

        $user = TelegramUser::findByChatId($reqData->chatId);
        if(!$user) {
            $reqData->text = UserText::unregistedText()->get();
            return Request::sendMessage($reqData->build());
        }

        $messageTextArr = explode(' ', $messageText);
        if(!empty($messageTextArr[0])) {
            $rtuSname = strtoupper($messageTextArr[0]);
        }
        if(count($messageTextArr) > 1 && !empty($messageTextArr[1])) {
            $noPort = strtoupper($messageTextArr[1]);
        }

        if(isset($rtuSname, $noPort)) {

            if($noPort == 'ALL') {
                return PortController::sendTextAllPort($rtuSname, $reqData->chatId);
            }
            
            $newosaseApiParams = [
                'searchRtuSname' => $rtuSname,
                'searchNoPort' => $noPort
            ];
            
            $request = static::request('Action/Typing');
            $request->params->chatId = $chatId;
            $request->send();

            $portData = static::getNewosasePortDetail($newosaseApiParams);
            if(!$portData) {
                $request = static::request('TextDefault');
                $request->params->chatId = $chatId;
                $request->setText(fn($text) => $text->addText('Terjadi masalah saat menghubungi server.'));
                $request->send();
            }

            if(!$portData['port']) {
                $request = static::request('TextDefault');
                $request->params->chatId = $chatId;
                $request->setText(fn($text) => $text->addText('Data Port tidak dapat ditemukan.'));
                $request->send();
            }

            $request = static::request('Port/TextDetailPort');
            $request->params->chatId = $chatId;
            $request->setPort($portData['port']);
            $response = $request->send();

            if(!isset($portData['chart'])) {
                return $response;
            }

            $request = static::request('PhotoDefault');
            $request->params->chatId = $chatId;
            $request->setPhoto($portData['chart']);
            return $request->send();

        }

        if(isset($rtuSname)) {
            
            $ports = PortController::fetchNewosasePorts(function($newosaseApi) use ($rtuSname) {
                $newosaseApi->request['query'] = [ 'searchRtuSname' => $rtuSname ];
                return $newosaseApi;
            }, $reqData->duplicate('chatId'));
            
            if(!$ports) {
                $reqData->text = 'Terjadi masalah saat menghubungi server.';
                return Request::sendMessage($reqData->build());
            }
            
            if(count($ports) < 1) {
                $reqData->text = 'Data Port RTU tidak dapat ditemukan.';
                return Request::sendMessage($reqData->build());
            }
            
            $btnPortRequest = PortController::getBtnPortList($rtuSname, $ports, $userChatId);
            $btnPortRequest->chatId = $reqData->chatId;
            return Request::sendMessage($btnPortRequest->build());

        }

        if($user['level'] == 'nasional') {
            
            $reqData->text = PortText::getRegionalInKeyboardText()->newLine(2)
                ->startItalic()
                ->addText('* Anda juga dapat memilih RTU dan Port dengan mengeikkan perintah /cekport [Kode RTU] [No. Port], contoh: /cekport RTU00-D7-BAL A-12')
                ->endItalic()
                ->get();

            $callbackData = new CallbackData('port.reg');
            $callbackData->limitAccess($userChatId);
            return RequestInKeyboard::regionalList(
                $reqData,
                fn($regional) => $callbackData->createEncodedData($regional['id'])
            );

        }

        if($user['level'] == 'regional') {
            
            $reqData->text = PortText::getWitelInKeyboardText()->newLine(2)
                ->startItalic()
                ->addText('* Anda juga dapat memilih RTU dan Port dengan mengeikkan perintah /cekport [Kode RTU] [No. Port], contoh: /cekport RTU00-D7-BAL A-12')
                ->endItalic()
                ->get();

            $callbackData = new CallbackData('port.wit');
            $callbackData->limitAccess($userChatId);
            return RequestInKeyboard::witelList(
                $user['regional_id'],
                $reqData,
                fn($witel) => $callbackData->createEncodedData($witel['id'])
            );

        }

        if($user['level'] == 'witel') {

            $reqData->text = PortText::getLocationInKeyboardText()->newLine(2)
                ->startItalic()
                ->addText('* Anda juga dapat memilih RTU dan Port dengan mengetikan perintah /cekport [Kode RTU] [No. Port], contoh: /cekport RTU00-D7-BAL A-12')
                ->endItalic()->get();

            $callbackData = new CallbackData('port.loc');
            $callbackData->limitAccess($userChatId);
            return RequestInKeyboard::locationList(
                $user['witel_id'],
                $reqData,
                fn($loc) => $callbackData->createEncodedData($loc['id'])
            );

        }
    }

    public static function checkLog()
    {
        return static::callModules('check-log');
    }

    public static function getBtnPortList($rtuSname, $portsData, $userChatId)
    {
        $callbackData = new CallbackData('port.port');
        $ports = array_map(function($port) use ($rtuSname, $userChatId, $callbackData) {

            $portData = [];
            array_push($portData, $rtuSname);
            if($port->result_type == 'port') {

                array_push($portData, $port->no_port);
                $callbackData->limitAccess($userChatId);

            } else {

                array_push($portData, '0');
                array_push($portData, $port->description);
                $callbackData->resetAccess();

            }

            $portData = implode(':', $portData);
            return [
                'text' => $port->no_port,
                'callback_data' => $callbackData->createEncodedData($portData)
            ];

        }, $portsData);

        $callbackData->limitAccess($userChatId);
        array_push($ports, [
            'text' => 'ALL PORT',
            'callback_data' => $callbackData->createEncodedData($rtuSname)
        ]);

        $inlineKeyboardData = array_reduce($ports, function($result, $port) {
            $lastIndex = count($result) - 1;
            if($lastIndex < 0 || count($result[$lastIndex]) == 3) {
                array_push($result, []);
                $lastIndex++;
            }

            array_push($result[$lastIndex], $port);
            return $result;
        }, []);

        $reqData = new RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->text = PortText::getPortInKeyboardText()->get();
        $reqData->replyMarkup = new InlineKeyboard(...$inlineKeyboardData);
        return $reqData;
    }

    public static function onSelectRegional($regionalId, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $chatId = $message->getChat()->getId();
        $userChatId = $callbackQuery->getFrom()->getId();
        $reqData = New RequestData();
        // $regional = Regional::find($regionalId);

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;
        $reqData->messageId = $message->getMessageId();
        // $reqData->text = PortText::getRegionalInKeyboardText()->newLine(2)
        //     ->addBold('=> ')->addText($regional['name'])
        //     ->get();
        // Request::editMessageText($reqData->build());
        Request::deleteMessage($reqData->duplicate('chatId', 'messageId')->build());

        $reqData1 = $reqData->duplicate('parseMode', 'chatId');
        $reqData1->text = PortText::getWitelInKeyboardText()->get();

        $callbackData = new CallbackData('port.wit');
        $callbackData->limitAccess($userChatId);
        return RequestInKeyboard::witelList(
            $regionalId,
            $reqData1,
            fn($witel) => $callbackData->createEncodedData($witel['id'])
        );
    }

    public static function onSelectWitel($witelId, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $userChatId = $callbackQuery->getFrom()->getId();
        $reqData = New RequestData();
        // $witel = Witel::find($witelId);

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();
        // $reqData->text = PortText::getWitelInKeyboardText()->newLine(2)
        //     ->addBold('=> ')->addText($witel['witel_name'])
        //     ->get();
        // Request::editMessageText($reqData->build());
        Request::deleteMessage($reqData->duplicate('chatId', 'messageId')->build());

        $reqData1 = $reqData->duplicate('parseMode', 'chatId');
        $reqData1->text = PortText::getLocationInKeyboardText()->get();

        $callbackData = new CallbackData('port.loc');
        $callbackData->limitAccess($userChatId);
        return RequestInKeyboard::locationList(
            $witelId,
            $reqData1,
            fn($loc) => $callbackData->createEncodedData($loc['id'])
        );
    }

    public static function onSelectLocation($locationId, $callbackQuery)
    {
        return static::callModules('on-select-location', compact('locationId', 'callbackQuery'));
    }

    public static function onSelectRtu($rtuSname, $callbackQuery)
    {   
        $message = $callbackQuery->getMessage();
        $userChatId = $callbackQuery->getFrom()->getId();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();

        Request::deleteMessage($reqData->duplicate('chatId', 'messageId')->build());

        $reqData1 = $reqData->duplicate('parseMode', 'chatId');
        $ports = PortController::fetchNewosasePorts(function($newosaseApi) use ($rtuSname) {
            $newosaseApi->request['query'] = [ 'searchRtuSname' => $rtuSname ];
            return $newosaseApi;
        }, $reqData->duplicate('chatId'));
        
        if(!$ports) {
            $reqData1->text = 'Terjadi masalah saat menghubungi server.';
            return Request::sendMessage($reqData1->build());
        }
        
        if(count($ports) < 1) {
            $reqData1->text = 'Data Port RTU tidak dapat ditemukan.';
            return Request::sendMessage($reqData1->build());
        }
        
        $btnPortRequest = PortController::getBtnPortList($rtuSname, $ports, $userChatId);
        $btnPortRequest->chatId = $reqData->chatId;
        return Request::sendMessage($btnPortRequest->build());
    }

    public static function onSelectPort($callbackData, $callbackQuery)
    {
        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $chatId = $message->getChat()->getId();

        $dataArr = explode(':', $callbackData);
        $rtuSname = isset($dataArr[0]) ? $dataArr[0] : null;
        $noPort = isset($dataArr[1]) && $dataArr[1] != '0' ? $dataArr[1] : null;
        $portDescr = isset($dataArr[2]) ? $dataArr[2] : null;

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;
        $reqData->messageId = $message->getMessageId();
        Request::deleteMessage($reqData->duplicate('chatId', 'messageId')->build());

        $newosaseApiParams = [];
        if($rtuSname && $portDescr) {
            $newosaseApiParams = [
                'searchRtuSname' => $rtuSname,
                'searchDescription' => $portDescr
            ];
        } elseif($rtuSname && $noPort) {
            $newosaseApiParams = [
                'searchRtuSname' => $rtuSname,
                'searchNoPort' => $noPort
            ];
        } elseif($rtuSname) {
            return PortController::sendTextAllPort($rtuSname, $reqData->chatId);
        }

        if(empty($newosaseApiParams)) {
            throw new \Error('Newosase API parameters is empty');
        }

        $request = static::request('Action/Typing');
        $request->params->chatId = $chatId;
        $request->send();

        $portData = static::getNewosasePortDetail($newosaseApiParams);
        if(!$portData) {
            $request = static::request('TextDefault');
            $request->params->chatId = $chatId;
            $request->setText(fn($text) => $text->addText('Terjadi masalah saat menghubungi server.'));
            $request->send();
        }

        if(!$portData['port']) {
            $request = static::request('TextDefault');
            $request->params->chatId = $chatId;
            $request->setText(fn($text) => $text->addText('Data Port tidak dapat ditemukan.'));
            $request->send();
        }

        $request = static::request('Port/TextDetailPort');
        $request->params->chatId = $chatId;
        $request->setPort($portData['port']);
        $response = $request->send();

        if(!isset($portData['chart'])) {
            return $response;
        }

        $request = static::request('PhotoDefault');
        $request->params->chatId = $chatId;
        $request->setPhoto($portData['chart']);
        return $request->send();
    }

    public static function fetchNewosasePorts(callable $callApi, $reqDataTyping = null)
    {
        if($reqDataTyping instanceof RequestData) {
            $reqDataTyping->action = ChatAction::TYPING;
            Request::sendChatAction($reqDataTyping->build());
        }

        $newosaseApi = $callApi(new NewosaseApi());
        $fetchResponse = $newosaseApi->sendRequest('GET', '/dashboard-service/dashboard/rtu/port-sensors');

        if(!$fetchResponse || !$fetchResponse->result->payload) {
            return null;
        }

        $ports = array_filter($fetchResponse->result->payload, function($port) {
            return $port->no_port != 'many';
        });

        return $ports;
    }

    public static function sendTextAllPort($rtuSname, $chatId)
    {
        $request = BotController::request('Action/Typing');
        $request->params->chatId = $chatId;
        $request->send();

        $newosaseApi = new NewosaseApi();
        $newosaseApi->request['query'] = [ 'searchRtuSname' => $rtuSname ];
        $fetchResponse = $newosaseApi->sendRequest('GET', '/dashboard-service/dashboard/rtu/port-sensors');
        if(!$fetchResponse || !is_array($fetchResponse->result->payload)) {
            
            $request = BotController::request('TextDefault');
            $request->params->chatId = $chatId;
            $request->setText(fn($text) => $text->addText('Terjadi masalah saat menghubungi server.'));
            return $request->send();

        }

        $ports = array_filter($fetchResponse->result->payload, function($port) {
            return $port->no_port != 'many';
        });

        if(count($ports) < 1) {

            $request = BotController::request('TextDefault');
            $request->params->chatId = $chatId;
            $request->setText(fn($text) => $text->addText('Data Port tidak dapat ditemukan.'));
            return $request->send();

        }
        
        $request = BotController::request('Port/ListTextCheckPort');
        $request->params->chatId = $chatId;
        $request->setPorts($ports);
        return $request->sendList();
    }

    public static function sendTextDetailPort(array $newosaseApiParams, $chatId)
    {
        BotController::sendDebugMessage($newosaseApiParams);
        $reqData = new RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;

        $ports = PortController::fetchNewosasePorts(function($newosaseApi) use ($newosaseApiParams) {
            $newosaseApi->request['query'] = $newosaseApiParams;
            return $newosaseApi;
        }, $reqData->duplicate('chatId'));

        if(!$ports) {
            $reqData->text = 'Terjadi masalah saat menghubungi server.';
            return Request::sendMessage($reqData->build());
        }
        
        if(count($ports) < 1) {
            $reqData->text = 'Data Port tidak dapat ditemukan.';
            return Request::sendMessage($reqData->build());
        }

        $request = static::request('Port/TextDetailPort');
        $request->params->chatId = $chatId;
        $request->setPort($ports[0]);
        return $request->send();
    }

    public static function getNewosasePortDetail(array $newosaseApiParams)
    {
        return static::callModules('get-newosase-port-detail', compact('newosaseApiParams',));
    }

    public static function onSelectRtuLog($rtuSname, $callbackQuery)
    {   
        return static::callModules('on-select-rtu-log', compact('rtuSname', 'callbackQuery'));
    }

    public static function onSelectLocationLog($locId, $callbackQuery)
    {   
        return static::callModules('on-select-loc-log', compact('locId', 'callbackQuery'));
    }

    public static function onSelectWitelLog($witelId, $callbackQuery)
    {   
        return static::callModules('on-select-witel-log', compact('witelId', 'callbackQuery'));
    }

    public static function onSelectRegionalLog($regionalId, $callbackQuery)
    {   
        return static::callModules('on-select-regional-log', compact('regionalId', 'callbackQuery'));
    }
}