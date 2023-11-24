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

class AlertController extends BotController
{
    protected static $callbacks = [
        'alert.exclusion' => 'onStartRequestExclusion',
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
        $message = AlertController::$command->getMessage();
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
            $alertGroups = TelegramUser::getAlertWitelGroup($telgUser['witel_id']);
        } elseif($telgUser['level'] == 'regional') {
            $alertGroups = TelegramUser::getAlertRegionalGroup($telgUser['regional_id']);
        } elseif($telgUser['level'] == 'nasional') {
            $alertGroups = TelegramUser::getAlertNasionalGroup();
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

        $request = BotController::request('TextAnswerSelect', [
            $messageText,
            $answer == 'continue' ? 'Lanjutkan' : 'Pengajuan dibatalkan.'
        ]);
        $request->params->chatId = $chatId;
        $request->params->messageId = $messageId;
        $response = $request->send();

        if($answer != 'continue') {
            return $response;
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
}