<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\ChatAction;

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
    ];

    public static function checkPort()
    {
        $message = PortController::$command->getMessage();
        $messageText = trim($message->getText(true));
        $userChatId = $message->getFrom()->getId();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();

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
            
            return PortController::sendTextDetailPort($rtuSname, 'port', $noPort, $reqData->chatId);

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

    public static function getBtnPortList($rtuSname, $portsData, $userChatId)
    {
        $ports = array_map(function($port) {

            $portKey = "$port->rtu_sname:$port->no_port";
            if($port->result_type != 'port') {
                $portKey .= ":$port->description";
            }

            return [
                'title' => $port->no_port,
                'key' => $portKey
            ];

        }, $portsData);

        array_push($ports, [
            'title' => 'ALL PORT',
            'key' => $rtuSname
        ]);

        $inlineKeyboardData = array_reduce($ports, function($result, $port) {
            $lastIndex = count($result) - 1;
            if($lastIndex < 0 || count($result[$lastIndex]) == 3) {
                array_push($result, []);
                $lastIndex++;
            }

            array_push($result[$lastIndex], [
                'text' => $port['title'],
                'callback_data' => 'port.port.'.$port['key']
            ]);

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
        $userChatId = $callbackQuery->getFrom()->getId();
        $reqData = New RequestData();
        // $regional = Regional::find($regionalId);

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
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

        $callbackData = new CallbackData('port.wit');
        $callbackData->limitAccess($userChatId);
        return RequestInKeyboard::locationList(
            $witelId,
            $reqData1,
            fn($loc) => $callbackData->createEncodedData($loc['id'])
        );
    }

    public static function onSelectLocation($locationId, $callbackQuery)
    {
        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $userChatId = $callbackQuery->getFrom()->getId();
        // $location = RtuLocation::find($locationId);
        $rtus = RtuList::getSnameOrderedByLocation($locationId);

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();
        // $reqData->text = PortText::getLocationInKeyboardText()->newLine(2)
        //     ->addBold('=> ')->addText($location['location_sname'])
        //     ->get();

        // Request::editMessageText($reqData->build());
        Request::deleteMessage($reqData->duplicate('chatId', 'messageId')->build());

        $reqData1 = $reqData->duplicate('parseMode', 'chatId');
        $reqData->text = PortText::getRtuInKeyboardText()->get();
        
        $callbackData = new CallbackData('port.rtu');
        $callbackData->limitAccess($userChatId);
        $inlineKeyboardData = array_reduce($rtus, function($result, $rtu) use ($callbackData) {
            $lastIndex = count($result) - 1;
            
            if($lastIndex < 0 || count($result[$lastIndex]) == 3) {
                array_push($result, []);
                $lastIndex++;
            }

            array_push($result[$lastIndex], [
                'text' => $rtu['sname'],
                'callback_data' => $callbackData->createEncodedData($rtu['id'])
            ]);

            return $result;
        }, []);
        
        $reqData->replyMarkup = new InlineKeyboard(...$inlineKeyboardData);
        return Request::sendMessage($reqData->build());
    }

    public static function onSelectRtu($rtuId, $callbackQuery)
    {   
        $message = $callbackQuery->getMessage();
        $userChatId = $message->getFrom()->getId();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();

        $rtu = RtuList::find($rtuId);
        // $reqData->text = PortText::getRtuInKeyboardText()->newLine(2)
        //     ->addBold('=> ')->addText($rtu['sname'])
        //     ->get();

        // Request::editMessageText($reqData->build());
        Request::deleteMessage($reqData->duplicate('chatId', 'messageId')->build());

        $reqData1 = $reqData->duplicate('parseMode', 'chatId');
        $ports = PortController::fetchNewosasePorts(function($newosaseApi) use ($rtu) {
            $newosaseApi->request['query'] = [ 'searchRtuSname' => $rtu['sname'] ];
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
        
        $btnPortRequest = PortController::getBtnPortList($rtu['sname'], $ports, $userChatId);
        $btnPortRequest->chatId = $reqData->chatId;
        return Request::sendMessage($btnPortRequest->build());
    }

    public static function onSelectPort($portData, $callbackQuery)
    {
        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        
        $portDataArr = explode(':', $portData);
        $rtuSname = $portDataArr[0];
        if(count($portDataArr) > 1) $noPort = $portDataArr[1];
        if(count($portDataArr) > 2) $portDescr = $portDataArr[2];

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();
        // $reqData->text = PortText::getPortInKeyboardText()->newLine(2)
        //     ->addBold('=> ')
        //     ->addText(isset($noPort) ? $noPort : 'ALL PORT')
        //     ->get();
            
        // Request::editMessageText($reqData->build());
        Request::deleteMessage($reqData->duplicate('chatId', 'messageId')->build());

        if(!isset($noPort)) {
            return PortController::sendTextAllPort($rtuSname, $reqData->chatId);
        }

        $identifierType = isset($portDescr) ? 'description' : 'port';
        $identifierKey = $identifierType == 'port' ? $noPort : $portDescr;
        return PortController::sendTextDetailPort($rtuSname, $identifierType, $identifierKey, $reqData->chatId);
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

    public static function sendTextDetailPort($rtuSname, $identifierType, $identifierKey, $chatId)
    {
        $reqData = new RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;

        if($identifierType == 'port') {

            $noPort = $identifierKey;
            $ports = PortController::fetchNewosasePorts(function($newosaseApi) use ($rtuSname, $noPort) {
                $newosaseApi->request['query'] = [
                    'searchRtuSname' => $rtuSname,
                    'searchNoPort' => $noPort
                ];
                return $newosaseApi;
            }, $reqData->duplicate('chatId'));

        } elseif($identifierType == 'description') {

            $portDescr = $identifierKey;
            $ports = PortController::fetchNewosasePorts(function($newosaseApi) use ($rtuSname, $portDescr) {
                $newosaseApi->request['query'] = [
                    'searchRtuSname' => $rtuSname,
                    'searchDescription' => $portDescr
                ];
                return $newosaseApi;
            }, $reqData->duplicate('chatId'));

        } else {

            $ports = null;

        }
        // BotController::sendDebugMessage([$identifierType, $identifierKey]);
        // return Request::emptyResponse();

        if(!$ports) {
            $reqData->text = 'Terjadi masalah saat menghubungi server.';
            return Request::sendMessage($reqData->build());
        }
        
        if(count($ports) < 1) {
            $reqData->text = 'Data Port tidak dapat ditemukan.';
            return Request::sendMessage($reqData->build());
        }
        
        $answerText = PortText::getDetailPortText($ports[0]);
        $reqData->text = $answerText->get();
        return Request::sendMessage($reqData->build());
    }
}