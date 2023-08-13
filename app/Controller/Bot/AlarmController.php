<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Request;
use App\Core\RequestData;
use App\Core\TelegramText;
use App\Controller\BotController;
use App\Model\TelegramUser;
use App\Model\RtuPortStatus;
use App\BuiltMessageText\UserText;
use App\BuiltMessageText\AlarmText;

class AlarmController extends BotController
{
    protected static $callbacks = [
        // 'user.regist_approval' => 'onRegist',
    ];

    public static function checkExistAlarm()
    {
        $message = AlarmController::$command->getMessage();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();

        $user = TelegramUser::findPicByChatId($reqData->chatId);
        if(!$user) {
            $reqData->text = UserText::unregistedText()->get();
            return Request::sendMessage($reqData->build());
        }

        if($user['level'] == 'nasional') {
            return Request::emptyResponse();
        }

        if($user['level'] == 'regional') {

            $ports = RtuPortStatus::getExistsAlarm([ 'regional' => $user['regional_id'] ]);
            $reqData->text = AlarmText::regionalAlarmText($user['regional_id'], $ports);

        } elseif($user['level'] == 'witel') {

            $ports = RtuPortStatus::getExistsAlarm([ 'witel' => $user['witel_id'] ]);
            $reqData->text = AlarmText::witelAlarmText($user['witel_id'], $ports)->get();
            
        } else {
            // $ports = RtuPortStatus::getByWitel($user['witel_id']);
            return Request::emptyResponse();
        }

        return Request::sendMessage($reqData->build());
    }
}