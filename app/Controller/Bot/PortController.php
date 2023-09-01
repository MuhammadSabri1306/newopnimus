<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\ChatAction;

use App\Core\RequestData;
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
    protected static $callbacks = [
        'port.select_regional' => 'onSelectRegional',
        'port.select_witel' => 'onSelectWitel',
        'port.select_location' => 'onSelectLocation',
        'port.select_rtu' => 'onSelectRtu',
        'port.select_port' => 'onSelectPort',
    ];

    public static function checkPort()
    {
        $message = PortController::$command->getMessage();
        $messageText = trim($message->getText(true));

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
            
            return PortController::sendTextDetailPort($rtuSname, $noPort, $reqData->chatId);

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
            
            $btnPortRequest = PortController::getBtnPortList($rtuSname, $ports);
            $btnPortRequest->chatId = $reqData->chatId;
            return Request::sendMessage($btnPortRequest->build());

        }

        if($user['level'] == 'nasional') {
            
            $reqData->text = PortText::getRegionalInKeyboardText()->newLine(2)
                ->startItalic()
                ->addText('* Anda juga dapat memilih RTU dan Port dengan mengeikkan perintah /cekport [Kode RTU] [No. Port], contoh: /cekport RTU00-D7-BAL A-12')
                ->endItalic()
                ->get();

            return RequestInKeyboard::regionalList(
                $reqData,
                fn($regional) => 'port.select_regional.'.$regional['id']
            );

        }

        if($user['level'] == 'regional') {
            
            $reqData->text = PortText::getWitelInKeyboardText()->newLine(2)
                ->startItalic()
                ->addText('* Anda juga dapat memilih RTU dan Port dengan mengeikkan perintah /cekport [Kode RTU] [No. Port], contoh: /cekport RTU00-D7-BAL A-12')
                ->endItalic()
                ->get();

            return RequestInKeyboard::witelList(
                $user['regional_id'],
                $reqData,
                fn($witel) => 'port.select_witel.'.$witel['id']
            );

        }

        if($user['level'] == 'witel') {

            $reqData->text = PortText::getLocationInKeyboardText()->newLine(2)
                ->startItalic()
                ->addText('* Anda juga dapat memilih RTU dan Port dengan mengetikan perintah /cekport [Kode RTU] [No. Port], contoh: /cekport RTU00-D7-BAL A-12')
                ->endItalic()->get();

            return RequestInKeyboard::locationList(
                $user['witel_id'],
                $reqData,
                fn($loc) => 'port.select_location.'.$loc['id']
            );

        }
    }

    public static function getBtnPortList($rtuSname, $portsData)
    {
        $ports = array_map(function($port) {
            return [
                'title' => $port->no_port,
                'key' => "$port->rtu_sname:$port->no_port"
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
                'callback_data' => 'port.select_port.'.$port['key']
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
        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $user = $callbackQuery->getFrom();
        $regional = Regional::find($regionalId);

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();
        $reqData->text = PortText::getRegionalInKeyboardText()->newLine(2)
            ->addBold('=> ')->addText($regional['name'])
            ->get();
        Request::editMessageText($reqData->build());

        $reqData1 = $reqData->duplicate('parseMode', 'chatId');
        $reqData1->text = PortText::getWitelInKeyboardText()->get();
        return RequestInKeyboard::witelList(
            $regionalId,
            $reqData1,
            fn($witel) => 'port.select_witel.'.$witel['id']
        );
    }

    public static function onSelectWitel($witelId, $callbackQuery)
    {
        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $user = $callbackQuery->getFrom();
        $witel = Witel::find($witelId);

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();
        $reqData->text = PortText::getWitelInKeyboardText()->newLine(2)
            ->addBold('=> ')->addText($witel['witel_name'])
            ->get();
        Request::editMessageText($reqData->build());

        $reqData1 = $reqData->duplicate('parseMode', 'chatId');
        $reqData1->text = PortText::getLocationInKeyboardText()->get();
        return RequestInKeyboard::locationList(
            $witelId,
            $reqData1,
            fn($loc) => 'port.select_location.'.$loc['id']
        );
    }

    public static function onSelectLocation($locationId, $callbackQuery)
    {
        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $user = $callbackQuery->getFrom();
        $location = RtuLocation::find($locationId);
        $rtus = RtuList::getSnameOrderedByLocation($locationId);

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();
        $reqData->text = PortText::getLocationInKeyboardText()->newLine(2)
            ->addBold('=> ')->addText($location['location_sname'])
            ->get();

        Request::editMessageText($reqData->build());

        $reqData1 = $reqData->duplicate('parseMode', 'chatId');
        $reqData->text = PortText::getRtuInKeyboardText()->get();

        $inlineKeyboardData = array_reduce($rtus, function($result, $rtu) {
            $lastIndex = count($result) - 1;
            
            if($lastIndex < 0 || count($result[$lastIndex]) == 3) {
                array_push($result, []);
                $lastIndex++;
            }

            array_push($result[$lastIndex], [
                'text' => $rtu['sname'],
                'callback_data' => 'port.select_rtu.'.$rtu['id']
            ]);

            return $result;
        }, []);
        
        $reqData->replyMarkup = new InlineKeyboard(...$inlineKeyboardData);
        return Request::sendMessage($reqData->build());
    }

    public static function onSelectRtu($rtuId, $callbackQuery)
    {   
        $message = $callbackQuery->getMessage();
        $user = $callbackQuery->getFrom();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();

        $rtu = RtuList::find($rtuId);
        $reqData->text = PortText::getRtuInKeyboardText()->newLine(2)
            ->addBold('=> ')->addText($rtu['sname'])
            ->get();

        Request::editMessageText($reqData->build());

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
        
        $btnPortRequest = PortController::getBtnPortList($rtu['sname'], $ports);
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

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();
        $reqData->text = PortText::getPortInKeyboardText()->newLine(2)
            ->addBold('=> ')
            ->addText(isset($noPort) ? $noPort : 'ALL PORT')
            ->get();
            
        Request::editMessageText($reqData->build());

        if(!isset($noPort)) {
            return PortController::sendTextAllPort($rtuSname, $reqData->chatId);
        }

        return PortController::sendTextDetailPort($rtuSname, $noPort, $reqData->chatId);
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
        return $fetchResponse->result->payload;
    }

    public static function sendTextAllPort($rtuSname, $chatId)
    {
        $reqData = new RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;

        $ports = PortController::fetchNewosasePorts(function($newosaseApi) use ($rtuSname) {
            $newosaseApi->request['query'] = [ 'searchRtuSname' => $rtuSname ];
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

        $answerText = PortText::getAllPortsText($ports)->getSplittedByLine(30);
        $textList = array_map(fn($textItem) => htmlspecialchars($textItem), $answerText);
        return BotController::sendMessageList($reqData, $textList, true);
    }

    public static function sendTextDetailPort($rtuSname, $noPort, $chatId)
    {
        $reqData = new RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;

        $ports = PortController::fetchNewosasePorts(function($newosaseApi) use ($rtuSname, $noPort) {
            $newosaseApi->request['query'] = [
                'searchRtuSname' => $rtuSname,
                'searchNoPort' => $noPort
            ];
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
        
        $answerText = PortText::getDetailPortText($ports[0]);
        $reqData->text = $answerText->get();
        return Request::sendMessage($reqData->build());
    }
}