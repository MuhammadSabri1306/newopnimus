<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\ChatAction;
use App\Core\RequestData;
use App\Core\TelegramText;
use App\Controller\BotController;
use App\Model\TelegramUser;
use App\Model\RtuPortStatus;
use App\BuiltMessageText\UserText;
use App\BuiltMessageText\AlarmText;
use App\ApiRequest\NewosaseApi;
use App\Request\RequestInKeyboard;

class AlarmController extends BotController
{
    protected static $callbacks = [
        'alarm.select_regional' => 'onSelectRegional'
    ];

    public static function checkExistAlarm()
    {
        $message = AlarmController::$command->getMessage();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();

        $user = TelegramUser::findByChatId($reqData->chatId);
        if(!$user) {
            $reqData->text = UserText::unregistedText()->get();
            return Request::sendMessage($reqData->build());
        }

        $reqDataTyping = $reqData->duplicate('chatId');
        $reqDataTyping->action = ChatAction::TYPING;
        Request::sendChatAction($reqDataTyping->build());

        if($user['level'] == 'nasional') {
            $reqData->text = 'Silahkan pilih Regional.';
            return RequestInKeyboard::regionalList(
                $reqData,
                fn($regional) => 'alarm.select_regional.'.$regional['id']
            );
        }

        $newosaseApi = new NewosaseApi();
        $newosaseApi->request['query'] = [ 'isAlert' => 1 ];
        if($user['level'] == 'regional') {
            $newosaseApi->request['query']['regionalId'] = $user['regional_id'];
        } elseif($user['level'] == 'witel') {
            $newosaseApi->request['query']['witelId'] = $user['witel_id'];
        }

        $fetResp = $newosaseApi->sendRequest('GET', '/dashboard-service/dashboard/rtu/port-sensors');
        if(!$fetResp) {
            $reqData->text = 'Terjadi masalah saat menghubungi server.';
            return Request::sendMessage($reqData->build());
        }

        $ports = array_filter($fetResp->result->payload, function($port) {
            return $port->no_port != 'many';
        });

        if(!$ports || count($ports) < 1) {
            $reqData->text = 'Data Port tidak dapat ditemukan.';
            return Request::sendMessage($reqData->build());
        }

        if($user['level'] == 'regional') {
            $regionalAlarmText = AlarmText::regionalAlarmText1($user['regional_id'], $ports)->getSplittedByLine(30);
            $textList = array_map(fn($textItem) => htmlspecialchars($textItem), $regionalAlarmText);
            return BotController::sendMessageList($reqData, $textList);
        }
        
        if($user['level'] == 'witel') {
            $witelAlarmText = AlarmText::witelAlarmText1($user['witel_id'], $ports)->getSplittedByLine(30);
            $textList = array_map(fn($textItem) => htmlspecialchars($textItem), $witelAlarmText);
            return BotController::sendMessageList($reqData, $textList, true);
        }
        
        // PIC
        return Request::emptyMessage();
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
        $reqData->text = TelegramText::create('Silahkan pilih Regional.')->newLine(2)
            ->addBold('=> ')->addText($regional['name'])
            ->get();
        Request::editMessageText($reqData->build());

        $regionalAlarmText = AlarmText::regionalAlarmText1($regionalId, $ports)->getSplittedByLine(30);
        $textList = array_map(fn($textItem) => htmlspecialchars($textItem), $regionalAlarmText);
        return BotController::sendMessageList($reqData->duplicate('parseMode', 'chatId'), $textList);
    }
}