<?php
namespace App\Controller\Bot;

use App\Core\CallbackData;
use App\Controller\BotController;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\AlarmHistory;

class StatisticController extends BotController
{
    public static $callbacks = [
        'stat.treg' => 'onSelectRegional',
        'stat.witl' => 'onSelectWitel'
    ];

    public static function daily()
    {
        $fromId = static::getMessage()->getFrom()->getId();
        $telgUser = static::getUser();
        if(!$telgUser) {
            
            $request = static::request('Error/TextUserUnidentified');
            $request->setTarget( static::getRequestTarget() );
            return $request->send();

        }

        if($telgUser['level'] == 'nasional') {

            $request = static::request('Area/SelectRegional');
            $request->setTarget( static::getRequestTarget() );
            $request->setRegionals(Regional::getSnameOrdered());

            $callbackData = new CallbackData('stat.treg');
            $callbackData->limitAccess($fromId);
            $request->setInKeyboard(function($inKeyboardItem, $regional) use ($callbackData) {
                $inKeyboardItem['callback_data'] = $callbackData->createEncodedData([
                    'c' => 'day', 'r' => $regional['id']
                ]);
                return $inKeyboardItem;
            });

            return $request->send();

        }

        if($telgUser['level'] == 'regional') {

            $request = static::request('Area/SelectWitel');
            $request->setTarget( static::getRequestTarget() );
            
            $witels = Witel::getNameOrdered($telgUser['regional_id']);
            $allWitelOption = [
                'id' => 'ALL',
                'witel_name' => 'PILIH SEMUA WITEL',
                'regional_id' => $telgUser['regional_id']
            ];
            $request->setWitels([ $allWitelOption, ...$witels ]);

            $callbackData = new CallbackData('stat.witl');
            $callbackData->limitAccess($fromId);
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
        $request->setTarget( static::getRequestTarget() );

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
        $fromId = static::getMessage()->getFrom()->getId();
        $telgUser = static::getUser();
        if(!$telgUser) {
            
            $request = BotController::request('Error/TextUserUnidentified');
            $request->setTarget( static::getRequestTarget() );
            return $request->send();

        }

        if($telgUser['level'] == 'nasional') {

            $request = static::request('Area/SelectRegional');
            $request->setTarget( static::getRequestTarget() );
            $request->setRegionals(Regional::getSnameOrdered());

            $callbackData = new CallbackData('stat.treg');
            $callbackData->limitAccess($fromId);
            $request->setInKeyboard(function($inKeyboardItem, $regional) use ($callbackData) {
                $inKeyboardItem['callback_data'] = $callbackData->createEncodedData([
                    'c' => 'month', 'r' => $regional['id']
                ]);
                return $inKeyboardItem;
            });

            return $request->send();

        }

        if($telgUser['level'] == 'regional') {

            $request = static::request('Area/SelectWitel');
            $request->setTarget( static::getRequestTarget() );
            
            $witels = Witel::getNameOrdered($telgUser['regional_id']);
            $allWitelOption = [
                'id' => 'ALL',
                'witel_name' => 'PILIH SEMUA WITEL',
                'regional_id' => $telgUser['regional_id']
            ];
            $request->setWitels([ $allWitelOption, ...$witels ]);

            $callbackData = new CallbackData('stat.witl');
            $callbackData->limitAccess($fromId);
            $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
                $val = [ 'c' => 'month', 'w' => $witel['id'] ];
                if($witel['id'] == 'ALL') $val['r'] = $witel['regional_id'];
                $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($val);
                return $inKeyboardItem;
            });

            return $request->send();

        }

        $request = static::request('Statistic/TextMonthlyWitel');
        $request->setTarget( static::getRequestTarget() );

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

    public static function onSelectRegional($params)
    {
        $message = static::getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();
        $fromId = static::getFrom()->getId();

        static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

        $request = static::request('Area/SelectWitel');
        $request->setTarget( static::getRequestTarget() );

        $statCategory = $params['c'];
        $regionalId = $params['r'];
        
        $witels = Witel::getNameOrdered($regionalId);
        $allWitelOption = [ 'title' => 'PILIH SEMUA WITEL' ];
        $request->setWitels([ $allWitelOption, ...$witels ]);

        $callbackData = new CallbackData('stat.witl');
        $callbackData->limitAccess($fromId);
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

    public static function onSelectWitel($params)
    {
        $message = static::getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();
        $fromId = static::getFrom()->getId();

        static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

        $statCategory = $params['c'];
        $witelId = $params['w'];

        if($witelId == 'ALL') {

            $regionalId = $params['r'];
            if($statCategory == 'month') {

                $request = static::request('Statistic/TextMonthlyRegional');
                $request->setTarget( static::getRequestTarget() );

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

                $request = static::request('Statistic/TextDailyRegional');
                $request->setTarget( static::getRequestTarget() );

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

            $request = static::request('Statistic/TextMonthlyWitel');
            $request->setTarget( static::getRequestTarget() );

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

        $request = static::request('Statistic/TextDailyWitel');
        $request->setTarget( static::getRequestTarget() );

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