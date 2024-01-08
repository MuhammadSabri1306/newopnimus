<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

use App\Controller\BotController;
use App\Controller\Bot\AdminController;
use App\Core\Conversation;
use App\Model\TelegramUser;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\Registration;
use App\Model\AlertUsers;

class AlertController extends BotController
{
    public static $callbacks = [
        'alert.exclusion' => 'onStartRequestExclusion',
        'alert.mode' => 'onChangeMode'
    ];

    public static function getAlertExclusionConversation()
    {
        if($command = AlertController::$command) {
            if($command->getMessage()) {
                $chatId = AlertController::$command->getMessage()->getChat()->getId();
                $userId = AlertController::$command->getMessage()->getFrom()->getId();
                return new Conversation('alert_exclusion', $userId, $chatId);
            } elseif($command->getCallbackQuery()) {
                $chatId = AlertController::$command->getCallbackQuery()->getMessage()->getChat()->getId();
                $userId = AlertController::$command->getCallbackQuery()->getFrom()->getId();
                return new Conversation('alert_exclusion', $userId, $chatId);
            }
        }

        return null;
    }

    public static function switch()
    {
        return static::callModules('switch');
    }

    public static function requestExclusion()
    {
        $message = AlertController::$command->getMessage();
        $chatId = $message->getChat()->getId();
        $telgUser = TelegramUser::findByChatId($chatId);
        
        if(!$telgUser) {
            $request = BotController::request('Error/TextUserUnidentified');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        if($telgUser['type'] == 'private') {
            $request = BotController::request('AlertStatus/TextExclusionNotProvided');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        $request = BotController::request('AlertStatus/SelectExclusionContinue');
        $request->params->chatId = $chatId;

        $request->setInKeyboard(function($inlineKeyboardData) {
            $inlineKeyboardData['continue']['callback_data'] = 'alert.exclusion.continue';
            $inlineKeyboardData['cancel']['callback_data'] = 'alert.exclusion.cancel';
            return $inlineKeyboardData;
        });

        return $request->send();

    }

    public static function submitExclusionRequest()
    {
        $message = AlertController::$command->getMessage();
        $chatId = $message->getChat()->getId();
        $telgUser = TelegramUser::findByChatId($chatId);
        
        if(!$telgUser) {
            $request = BotController::request('Error/TextUserUnidentified');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        if($telgUser['type'] == 'private') {
            $request = BotController::request('AlertStatus/TextExclusionNotProvided');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        $conversation = AlertController::getAlertExclusionConversation();
        $messageText = trim($message->getText(true));

        $conversation->description = $messageText;
        $conversation->nextStep();
        $conversation->commit();
        $conversation->done();

        if($telgUser['level'] == 'witel') {
            $alertUsers = AlertUsers::getByLevel('witel', $telgUser['witel_id']);
        } elseif($telgUser['level'] == 'regional') {
            $alertUsers = AlertUsers::getByLevel('regional', $telgUser['regional_id']);
        } elseif($telgUser['level'] == 'nasional') {
            $alertUsers = AlertUsers::getByLevel('nasional');
        }

        $alertGroups = [];
        if(is_array($alertUsers) && count($alertUsers) > 0) {
            $alertTelgUsers = TelegramUser::getByIds( array_column($alertUsers, 'id') );
            foreach($alertTelgUsers as $alertTelgUser) {
                if(!$alertTelgUser['is_pic']) {
                    array_push($alertGroups, $alertTelgUser);
                }
            }
        }

        $registration = Registration::create([
            'request_type' => 'alert_exclusion',
            'chat_id' => $telgUser['chat_id'],
            'user_id' => $telgUser['user_id'],
            'data' => [
                'description' => $conversation->description,
                'request_group' => $telgUser,
                'alerted_groups' => $alertGroups
            ]
        ]);

        $request = BotController::request('AlertStatus/TextExclusionSubmitted');
        $request->params->chatId = $chatId;
        $request->setGroupName($telgUser['username']);
        $response = $request->send();
        
        AdminController::whenRequestAlertExclusion($registration['id']);

        return $response;

    }

    public static function onStartRequestExclusion($answer, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $messageText = $message->getText(true);
        $chatId = $message->getChat()->getId();
        $userId = $callbackQuery->getFrom()->getId();

        static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

        if($answer != 'continue') {

            $request = static::request('TextDefault');
            $request->params->chatId = $chatId;
            $request->setText(fn($text) => $text->addText('Pengajuan dibatalkan.'));
            return $request->send();
        }

        $conversation = AlertController::getAlertExclusionConversation();
        if(!$conversation->isExists()) {
            $conversation->create();
        }
        
        $request = BotController::request('TextDefault');
        $request->params->chatId = $chatId;
        $request->setText(fn($text) => $text->addText('Mohon deskripsikan justifikasi untuk melakukan penambahan Alerting.'));
        return $request->send();
    }

    public static function whenRequestExclusionReviewed(bool $isApproved, $registId)
    {
        $registration = Registration::find($registId);
        if(!$registration) {
            return Request::emptyResponse();
        }

        $request = BotController::request('TextDefault');
        $request->params->chatId = $registration['chat_id'];

        $reviewDate = $registration['updated_at'];
        if($isApproved) {
            $request->setText(function($text) use ($reviewDate) {
                return $text->addBold('Pengajuan Penambahan Alerting diterima.')->newLine()
                    ->addItalic($reviewDate)->newLine(2)
                    ->addText('Permintaan terkait penambahan alerting telah mendapat persetujuan Admin. ')
                    ->addText('Dengan ini Alerting Grup telah dinyalakan.')->newLine()
                    ->addText('Terima kasih.');
            });
        } else {
            $request->setText(function($text) use ($reviewDate) {
                return $text->addBold('Pengajuan Penambahan Alerting ditolak.')->newLine()
                    ->addItalic($reviewDate)->newLine(2)
                    ->addText('Mohon maaf, permintaan tidak mendapat persetujuan Admin. ')
                    ->addText('Anda dapat berkoordinasi dengan Admin untuk mendapatkan informasi terkait.')->newLine()
                    ->addText('Terima kasih.');
            });
        }

        return $request->send();
    }

    public static function changeAlertMode()
    {
        return static::callModules('change-alert-mode');
    }

    public static function onChangeMode($modeId, $callbackQuery)
    {
        return static::callModules('on-change-mode', compact('modeId', 'callbackQuery'));
    }
}