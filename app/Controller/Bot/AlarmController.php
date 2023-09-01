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

class AlarmController extends BotController
{
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
            return Request::emptyResponse();
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

        $ports = $fetResp->result->payload;
        if(!$ports || count($ports) < 1) {
            $reqData->text = 'Data Port tidak dapat ditemukan.';
            return Request::sendMessage($reqData->build());
        }

        if($user['level'] == 'regional') {

            $regionalAlarmText = AlarmText::regionalAlarmText1($user['regional_id'], $ports)->getSplittedByLine(30);
            $textList = array_map(fn($textItem) => htmlspecialchars($textItem), $regionalAlarmText);
            return BotController::sendMessageList($reqData, $textList);
            
        } elseif($user['level'] == 'witel') {

            $witelAlarmText = AlarmText::witelAlarmText1($user['witel_id'], $ports)->getSplittedByLine(30);
            $textList = array_map(fn($textItem) => htmlspecialchars($textItem), $witelAlarmText);
            return BotController::sendMessageList($reqData, $textList, true);
            
        } else {
            return Request::emptyResponse();
        }

        $response = Request::sendMessage($reqData->build());
        if($response->isOk()) {
            return $response;
        }
        return BotController::sendDebugMessage($response);
    }
}