<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\ChatAction;
use Goat1000\SVGGraph\SVGGraph;

use App\Core\RequestData;
use App\Core\Conversation;
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

        'portsts.type' => 'onSelectStatusType',
        'portstsa.reg' => 'onSelectRegionalStatusCatuan',
        'portstsa.wit' => 'onSelectWitelStatusCatuan',
    ];

    public static function getCekPortAllConversation()
    {
        if($command = PortController::$command) {
            if($command->getMessage()) {
                $chatId = PortController::$command->getMessage()->getChat()->getId();
                $userId = PortController::$command->getMessage()->getFrom()->getId();
                return new Conversation('cekportall', $userId, $chatId);
            } elseif($command->getCallbackQuery()) {
                $chatId = PortController::$command->getCallbackQuery()->getMessage()->getChat()->getId();
                $userId = PortController::$command->getCallbackQuery()->getFrom()->getId();
                return new Conversation('cekportall', $userId, $chatId);
            }
        }

        return null;
    }

    public static function checkPort(): ServerResponse
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
            
            $btnPortRequests = PortController::getBtnPortList($rtuSname, $ports, $userChatId);
            if(count($btnPortRequests) < 1) {
                return Request::emptyResponse();
            }

            $sendedMsgIds = [];
            for($i=0; $i<count($btnPortRequests); $i++) {

                $btnPortRequests[$i]->chatId = $reqData->chatId;
                $response = Request::sendMessage($btnPortRequests[$i]->build());
                if(!$response->isOk()) {
                    $i = count($btnPortRequests);
                } else {
                    array_push($sendedMsgIds, $response->getResult()->getMessageId());
                }
    
            }

            if(count($sendedMsgIds) < 1) return $response;

            $conversation = new Conversation('cekportall', $userChatId, $chatId, [
                'call' => function($db, $params) {
                    return $db->queryFirstRow(
                        "SELECT * FROM conversation WHERE status='active' AND name=%s_name AND user_id=%i_userid",
                        [ 'name' => $params['name'], 'userid' => $params['userId'] ]
                    );
                }
            ]);
            if(!$conversation->isExists()) $conversation->create();
            $conversation->messageIds = $sendedMsgIds;
            $conversation->commit();

            return $response;

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
        $inKeyboard = []; $index = 0; $itemCount = 0;

        array_push($inKeyboard, [
            [ 'text' => 'ALL PORT', 'callback_data' => $callbackData->createEncodedData($rtuSname) ]
        ]);
        $index++;

        for($i=0; $i<count($portsData); $i++) {

            $port = $portsData[$i];
            $values = [ $rtuSname ];

            if($port->result_type == 'port') {
                array_push($values, $port->no_port);
                $callbackData->limitAccess($userChatId);
            } else {
                array_push($values, '0', $port->description);
                $callbackData->resetAccess();
            }

            if( !isset($inKeyboard[$index]) ) {
                array_push($inKeyboard, []);
                $itemCount = 0;
            }

            $portData = $callbackData->createEncodedData( implode(':', $values) );
            array_push($inKeyboard[$index], [
                'text' => $port->no_port,
                'callback_data' => $portData
            ]);

            $itemCount++;
            if($itemCount >= 3) $index++;

        }

        $maxInKeyboardCount = 30;
        $inKeyboardCount = count($inKeyboard);
        $useSplit = $inKeyboardCount > $maxInKeyboardCount;
        $useAvgSplit = $useSplit && ($inKeyboardCount % $maxInKeyboardCount < $maxInKeyboardCount / 2);

        if(!$useSplit) {

            $reqData = new RequestData();
            $reqData->parseMode = 'markdown';
            $reqData->text = PortText::getPortInKeyboardText()->get();
            $reqData->replyMarkup = new InlineKeyboard(...$inKeyboard);
            return [ $reqData ];

        }

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

        $reqDatas = [];
        $reqData = new RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->text = PortText::getPortInKeyboardText()->get();
        foreach($splittedInKeyboard as $inKeyboard) {
            $reqData->replyMarkup = new InlineKeyboard(...$inKeyboard);
            array_push($reqDatas, $reqData->duplicate('parseMode', 'text', 'replyMarkup'));
        }
        return $reqDatas;
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
        $chatId = $message->getChat()->getId();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;
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

        $btnPortRequests = PortController::getBtnPortList($rtuSname, $ports, $userChatId);
        if(count($btnPortRequests) < 1) {
            return Request::emptyResponse();
        }

        $sendedMsgIds = [];
        for($i=0; $i<count($btnPortRequests); $i++) {

            $btnPortRequests[$i]->chatId = $reqData->chatId;
            $response = Request::sendMessage($btnPortRequests[$i]->build());
            if(!$response->isOk()) {
                $i = count($btnPortRequests);
            } else {
                array_push($sendedMsgIds, $response->getResult()->getMessageId());
            }

        }

        if(count($sendedMsgIds) < 1) return $response;

        $conversation = new Conversation('cekportall', $userChatId, $chatId, [
            'call' => function($db, $params) {
                return $db->queryFirstRow(
                    "SELECT * FROM conversation WHERE status='active' AND name=%s_name AND user_id=%i_userid",
                    [ 'name' => $params['name'], 'userid' => $params['userId'] ]
                );
            }
        ]);
        if(!$conversation->isExists()) $conversation->create();
        $conversation->messageIds = $sendedMsgIds;
        $conversation->commit();

        return $response;
    }

    public static function onSelectPort($callbackData, $callbackQuery)
    {
        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $chatId = $message->getChat()->getId();
        $fromId = $callbackQuery->getFrom()->getId();

        $dataArr = explode(':', $callbackData);
        $rtuSname = isset($dataArr[0]) ? $dataArr[0] : null;
        $noPort = isset($dataArr[1]) && $dataArr[1] != '0' ? $dataArr[1] : null;
        $portDescr = isset($dataArr[2]) ? $dataArr[2] : null;

        $conversation = new Conversation('cekportall', $fromId, $chatId, [
            'call' => function($db, $params) {
                return $db->queryFirstRow(
                    "SELECT * FROM conversation WHERE status='active' AND name=%s_name AND user_id=%i_userid",
                    [ 'name' => $params['name'], 'userid' => $params['userId'] ]
                );
            }
        ]);

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;
        if($conversation->isExists()) {
            $messageIds = $conversation->messageIds ?? [];
            $conversation->done();
            foreach($messageIds as $msgId) {
                $reqData->messageId = $msgId;
                Request::deleteMessage($reqData->duplicate('chatId', 'messageId')->build());
            }
        }


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
        
        $request = BotController::request('Port/TextPortList');
        $request->params->chatId = $chatId;
        $request->setPorts($ports);
        return $request->send();
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

    public static function checkStatus()
    {
        return static::callModules('check-status');
    }

    public static function onSelectStatusType($statusTypeKey, $callbackQuery)
    {
        return static::callModules(
            'on-select-status-type',
            compact('statusTypeKey', 'callbackQuery')
        );
    }

    public static function onSelectRegionalStatusCatuan($regionalId, $callbackQuery)
    {
        return static::callModules(
            'on-select-regional-status-catuan',
            compact('regionalId', 'callbackQuery')
        );
    }

    public static function onSelectWitelStatusCatuan($witelId, $callbackQuery)
    {
        return static::callModules(
            'on-select-witel-status-catuan',
            compact('witelId', 'callbackQuery')
        );
    }
}