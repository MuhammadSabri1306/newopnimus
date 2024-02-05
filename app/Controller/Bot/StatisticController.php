<?php
namespace App\Controller\Bot;

use App\Core\CallbackData;
use App\Controller\BotController;
use App\Model\TelegramUser;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\AlarmPortStatus;
use App\Model\AlarmHistory;

class StatisticController extends BotController
{
    public static $callbacks = [
        'stat.treg' => 'onSelectRegional',
        'stat.witl' => 'onSelectWitel'
    ];

    public static function daily()
    {
        $message = StatisticController::$command->getMessage();
        $chatId = $message->getChat()->getId();
        $userChatId = $message->getFrom()->getId();

        $telgUser = TelegramUser::findByChatId($chatId);
        if(!$telgUser) {
            
            $request = BotController::request('Error/TextUserUnidentified');
            $request->params->chatId = $chatId;
            return $request->send();

        }

        if($telgUser['level'] == 'nasional') {

            $request = BotController::request('Area/SelectRegional');
            $request->params->chatId = $chatId;
            $request->setRegionals(Regional::getSnameOrdered());

            $callbackData = new CallbackData('stat.treg');
            $callbackData->limitAccess($userChatId);
            $request->setInKeyboard(function($inKeyboardItem, $regional) use ($callbackData) {
                $inKeyboardItem['callback_data'] = $callbackData->createEncodedData([
                    'c' => 'day', 'r' => $regional['id']
                ]);
                return $inKeyboardItem;
            });

            return $request->send();

        }

        if($telgUser['level'] == 'regional') {

            $request = BotController::request('Area/SelectWitel');
            $request->params->chatId = $chatId;
            
            $witels = Witel::getNameOrdered($telgUser['regional_id']);
            $allWitelOption = [
                'id' => 'ALL',
                'witel_name' => 'PILIH SEMUA WITEL',
                'regional_id' => $telgUser['regional_id']
            ];
            $request->setWitels([ $allWitelOption, ...$witels ]);

            $callbackData = new CallbackData('stat.witl');
            $callbackData->limitAccess($userChatId);
            $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
                $val = [ 'c' => 'day', 'w' => $witel['id'] ];
                if($witel['id'] == 'ALL') $val['r'] = $witel['regional_id'];
                $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($val);
                return $inKeyboardItem;
            });

            return $request->send();

        }

        // LEVEL = WITEL
        $request = BotController::request('Statistic/TextDailyWitel');
        $request->params->chatId = $chatId;

        $witel = Witel::find($telgUser['witel_id']);
        $request->setWitel($witel);

        $dateTime = new \DateTime();
        $dateTime->setTime(0, 0, 0);
        $alarmStat = AlarmHistory::getStat([
            'witelId' => $telgUser['witel_id'],
            'startDate' => $dateTime,
        ]);
        $request->setAlarmStat($alarmStat);
        
        return $request->send();
    }

    public static function monthly()
    {
        $message = StatisticController::$command->getMessage();
        $chatId = $message->getChat()->getId();
        $userChatId = $message->getFrom()->getId();

        $telgUser = TelegramUser::findByChatId($chatId);
        if(!$telgUser) {
            
            $request = BotController::request('Error/TextUserUnidentified');
            $request->params->chatId = $chatId;
            return $request->send();

        }

        if($telgUser['level'] == 'nasional') {

            $request = BotController::request('Area/SelectRegional');
            $request->params->chatId = $chatId;
            $request->setRegionals(Regional::getSnameOrdered());

            $callbackData = new CallbackData('stat.treg');
            $callbackData->limitAccess($userChatId);
            $request->setInKeyboard(function($inKeyboardItem, $regional) use ($callbackData) {
                $inKeyboardItem['callback_data'] = $callbackData->createEncodedData([
                    'c' => 'month', 'r' => $regional['id']
                ]);
                return $inKeyboardItem;
            });

            return $request->send();

        }

        if($telgUser['level'] == 'regional') {

            $request = BotController::request('Area/SelectWitel');
            $request->params->chatId = $chatId;
            
            $witels = Witel::getNameOrdered($telgUser['regional_id']);
            $allWitelOption = [
                'id' => 'ALL',
                'witel_name' => 'PILIH SEMUA WITEL',
                'regional_id' => $telgUser['regional_id']
            ];
            $request->setWitels([ $allWitelOption, ...$witels ]);

            $callbackData = new CallbackData('stat.witl');
            $callbackData->limitAccess($userChatId);
            $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
                $val = [ 'c' => 'month', 'w' => $witel['id'] ];
                if($witel['id'] == 'ALL') $val['r'] = $witel['regional_id'];
                $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($val);
                return $inKeyboardItem;
            });

            return $request->send();

        }

        $request = BotController::request('Statistic/TextMonthlyWitel');
        $request->params->chatId = $chatId;

        $witel = Witel::find($telgUser['witel_id']);
        $request->setWitel($witel);

        $dateTime = new \DateTime();
        $dateTime->modify('first day of this month');
        $dateTime->setTime(0, 0, 0);
        $alarmStat = AlarmHistory::getStat([
            'witelId' => $telgUser['witel_id'],
            'startDate' => $dateTime,
        ]);
        $request->setAlarmStat($alarmStat);

        return $request->send();
    }

    public static function onSelectRegional($params, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $request = BotController::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();

        $request = BotController::request('Area/SelectWitel');
        $request->params->chatId = $chatId;

        $statCategory = $params['c'];
        $regionalId = $params['r'];
        
        $witels = Witel::getNameOrdered($regionalId);
        $allWitelOption = [ 'title' => 'PILIH SEMUA WITEL' ];
        $request->setWitels([ $allWitelOption, ...$witels ]);

        $callbackData = new CallbackData('stat.witl');
        $callbackData->limitAccess($userChatId);
        $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData, $statCategory, $regionalId) {
            if( isset($witel['title']) && $witel['title'] == 'PILIH SEMUA WITEL' ) {
                $inKeyboardItem['callback_data'] = $callbackData->createEncodedData([
                        'c' => $statCategory, 'w' => 'ALL', 'r' => $regionalId
                    ]);
            } else {
                $inKeyboardItem['callback_data'] = $callbackData->createEncodedData([
                        'c' => $statCategory, 'w' => $witel['id']
                    ]);
            }
            return $inKeyboardItem;
        });

        return $request->send();
    }

    public static function onSelectWitel($params, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $request = BotController::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();

        $statCategory = $params['c'];
        $witelId = $params['w'];

        if($witelId == 'ALL') {

            $regionalId = $params['r'];
            if($statCategory == 'month') {

                $request = BotController::request('Statistic/TextMonthlyRegional');
                $request->params->chatId = $chatId;

                $regional = Regional::find($regionalId);
                $request->setRegional($regional);

                $dateTime = new \DateTime();
                $dateTime->modify('first day of this month');
                $dateTime->setTime(0, 0, 0);
                $alarmStat = AlarmHistory::getStat([
                    'regionalId' => $regionalId,
                    'startDate' => $dateTime,
                ]);
                $request->setAlarmStat($alarmStat);

                return $request->send();

            } else {

                $request = BotController::request('Statistic/TextDailyRegional');
                $request->params->chatId = $chatId;

                $regional = Regional::find($regionalId);
                $request->setRegional($regional);

                $dateTime = new \DateTime();
                $dateTime->setTime(0, 0, 0);
                $alarmStat = AlarmHistory::getStat([
                    'regionalId' => $regionalId,
                    'startDate' => $dateTime,
                ]);
                $request->setAlarmStat($alarmStat);

                return $request->send();

            }

        }

        if($statCategory == 'month') {

            $request = BotController::request('Statistic/TextMonthlyWitel');
            $request->params->chatId = $chatId;

            $witel = Witel::find($witelId);
            $request->setWitel($witel);

            $dateTime = new \DateTime();
            $dateTime->modify('first day of this month');
            $dateTime->setTime(0, 0, 0);
            $alarmStat = AlarmHistory::getStat([
                'witelId' => $witelId,
                'startDate' => $dateTime,
            ]);
            $request->setAlarmStat($alarmStat);

            return $request->send();

        }

        $request = BotController::request('Statistic/TextDailyWitel');
        $request->params->chatId = $chatId;

        $witel = Witel::find($witelId);
        $request->setWitel($witel);

        $dateTime = new \DateTime();
        $dateTime->setTime(0, 0, 0);
        $alarmStat = AlarmHistory::getStat([
            'witelId' => $witelId,
            'startDate' => $dateTime,
        ]);
        $request->setAlarmStat($alarmStat);

        return $request->send();
    }
}