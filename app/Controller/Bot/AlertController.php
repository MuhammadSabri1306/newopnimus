<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;

use App\Core\RequestData;
use App\Core\TelegramText;

use App\Controller\BotController;
use App\Model\TelegramUser;
use App\Model\Regional;
use App\Model\Witel;

class AlertController extends BotController
{
    public static function switch()
    {
        $message = PicController::$command->getMessage();
        $chatId = $message->getChat()->getId();
        $messageText = strtolower(trim($message->getText(true)));

        $telgUser = TelegramUser::findByChatId($chatId);
        if(!$telgUser) {

            $request = BotController::request('Error/TextUserUnidentified');
            $request->params->chatId = $chatId;
            return $request->send();

        }

        if($telgUser['type'] == 'private' && !$telgUser['is_pic']) {

            $request = BotController::request('AlertStatus/TextFeatureNotProvided');
            $request->params->chatId = $chatId;
            return $request->send();

        }

        $alertStatus = null;
        if($messageText == 'on') {
            $alertStatus = 1;
        } elseif($messageText == 'off') {
            $alertStatus = 0;
        }

        if($alertStatus === null) {

            $request = BotController::request('AlertStatus/TextIncompatibleFormat');
            $request->params->chatId = $chatId;
            return $request->send();

        } elseif($alertStatus == 1 && !$telgUser['is_pic']) {

            $alertGroup = null;
            if($telgUser['level'] == 'witel') {
                $alertGroup = TelegramUser::findAlertWitelGroup($telgUser['witel_id']);
            } elseif($telgUser['level'] == 'regional') {
                $alertGroup = TelegramUser::findAlertRegionalGroup($telgUser['regional_id']);
            } elseif($telgUser['level'] == 'nasional') {
                $alertGroup = TelegramUser::findAlertNasionalGroup();
            }

            if($alertGroup) {

                $request = BotController::request('AlertStatus/TextAlertGroupHasExists');
                $request->params->chatId = $chatId;
                $request->setGroupTitle($alertGroup['username']);

                if($alertGroup['level'] == 'witel') {
                    $witel = Witel::find($alertGroup['witel_id']);
                    $request->setLevelName($witel['witel_name']);
                } elseif($alertGroup['level'] == 'regional') {
                    $regional = Regional::find($alertGroup['regional_id']);
                    $request->setLevelName($regional['name']);
                } elseif($alertGroup['level'] == 'nasional') {
                    $request->setLevelName('NASIONAL');
                }

                $request->buildText();
                return $request->send();

            }

        }

        TelegramUser::update($telgUser['id'], [
            'alert_status' => $alertStatus
        ]);

        $request = BotController::request('AlertStatus/TextSwitchSuccess', [ $alertStatus ]);
        $request->params->chatId = $chatId;
        return $request->send();
    }
}