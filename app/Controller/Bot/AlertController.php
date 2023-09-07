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

class AlertController extends BotController
{
    public static function switch()
    {
        $message = PicController::$command->getMessage();
        $chatId = $message->getChat()->getId();
        $messageText = strtolower(trim($message->getText(true)));

        $reqData = new RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;

        $telgUser = TelegramUser::findByChatId($chatId);
        if(!$telgUser) {
            
            $reqData->text = TelegramText::create('Anda belum terdaftar sebagai pengguna ')
                ->addBold('Opnimus')->addText('.')
                ->get();
            return Request::sendMessage($reqData->build());

        }

        $alertStatus = null;
        if($messageText == 'on') {
            $alertStatus = 1;
        } elseif($messageText == 'off') {
            $alertStatus = 0;
        }

        if(is_null($alertStatus)) {

            $reqData->text = TelegramText::create('Format: ')
                ->addCode('/alert [ON/OFF]')
                ->get();
            return Request::sendMessage($reqData->build());

        }

        TelegramUser::update($telgUser['id'], [
            'alert_status' => $alertStatus
        ]);

        if($alertStatus) {
            $reqData->text = 'Berhasil menyalakan Alarm kembali.';
        } else {
            $reqData->text = 'Berhasil mematikan Alarm.';
        }
        return Request::sendMessage($reqData->build());
    }
}