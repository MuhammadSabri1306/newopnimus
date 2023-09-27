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

useHelper('telegram-callback');

class RtuController extends BotController
{
    public static $callbacks = [
        'rtu.select_regional' => 'onSelectRegional',
        'rtu.select_witel' => 'onSelectWitel',
        'rtu.select_loc' => 'onSelectLocation',
        'rtu.select_rtu' => 'onSelectRtu',
    ];

    public static function checkRtu()
    {
        $message = RtuController::$command->getMessage();
        $messageText = trim($message->getText(true));
        $chatId = $message->getChat()->getId();

        $user = TelegramUser::findByChatId($chatId);
        if(!$user) {
            $request = BotController::request('Error/TextUserUnidentified');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        $messageTextArr = explode(' ', $messageText);
        if(!empty($messageTextArr[0])) {
            $rtuSname = strtoupper($messageTextArr[0]);
        }

        if(isset($rtuSname)) {
            
            $rtu = RtuList::findBySname($rtuSname);
            return RtuController::sendRtuDetail($chatId, $rtu);

        }
        
        if($user['level'] == 'nasional') {

            $request = BotController::request('Area/SelectRegional');
            $questionText = $request->getText()->newLine()
                ->addItalic('* Anda juga dapat memilih RTU dan Port dengan mengetikkan perintah /cekrtu [Kode RTU], e.g: /cekrtu RTU00-D7-BAL')
                ->get();

            $request->params->chatId = $chatId;
            $request->params->text = $questionText;
            $request->setData('regionals', Regional::getSnameOrdered());
            $request->setInKeyboard(function($item, $regional) {
                $item['callback_data'] = encodeCallbackData('rtu.select_regional', null, $regional['id']);
                return $item;
            });
            return $request->send();
            
        }
        
        if($user['level'] == 'regional') {

            $request = BotController::request('Area/SelectWitel');
            $questionText = $request->getText()->newLine()
                ->addItalic('* Anda juga dapat memilih RTU dan Port dengan mengetikkan perintah /cekrtu [Kode RTU], e.g: /cekrtu RTU00-D7-BAL')
                ->get();

            $request->params->chatId = $chatId;
            $request->params->text = $questionText;
            $request->setData('witels', Witel::getNameOrdered($user['regional_id']));
            $request->setInKeyboard(function($item, $witel) {
                $item['callback_data'] = encodeCallbackData(
                    'rtu.select_witel',
                    $item['text'],
                    $witel['id']
                );
                return $item;
            });
            return $request->send();

        }
        
        if($user['level'] == 'witel') {

            $request = BotController::request('Area/SelectLocation');
            $request->setData('locations', RtuLocation::getSnameOrderedByWitel($user['witel_id']));
            $request->params->chatId = $chatId;
            $request->params->text = $request->getText()->newLine()
                ->addItalic('* Anda juga dapat memilih RTU dan Port dengan mengetikkan perintah /cekrtu [Kode RTU], e.g: /cekrtu RTU00-D7-BAL')
                ->get();
            
            $request->setInKeyboard(function($item, $loc) {
                $item['callback_data'] = encodeCallbackData(
                    'rtu.select_loc',
                    $item['text'],
                    $loc['id']
                );
                return $item;
            });

            $response = $request->send();
            return $response;

        }

        return Request::emptyResponse();
    }

    public static function onSelectRegional($option, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $regional = Regional::find($option['value']);
        $request = BotController::request('TextAnswerSelect', [
            BotController::request('Area/SelectRegional')->getText()->get(),
            $regional['name']
        ]);
        $request->params->chatId = $chatId;
        $request->params->messageId = $messageId;
        $response = $request->send();

        $request = BotController::request('Area/SelectWitel');
        $request->setData('witels', Witel::getNameOrdered($option['value']));
        $request->params->chatId = $chatId;
        
        $request->setInKeyboard(function($item, $witel) {
            $item['callback_data'] = encodeCallbackData(
                'rtu.select_witel',
                $item['text'],
                $witel['id']
            );
            return $item;
        });

        return $request->send();
    }

    public static function onSelectWitel($option, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $request = BotController::request('TextAnswerSelect', [
            BotController::request('Area/SelectWitel')->getText()->get(),
            $option['title']
        ]);
        $request->params->chatId = $chatId;
        $request->params->messageId = $messageId;
        $response = $request->send();

        $request = BotController::request('Area/SelectLocation');
        $request->setData('locations', RtuLocation::getSnameOrderedByWitel($option['value']));
        $request->params->chatId = $chatId;
        
        $request->setInKeyboard(function($item, $loc) {
            $item['callback_data'] = encodeCallbackData(
                'rtu.select_loc',
                $item['text'],
                $loc['id']
            );
            return $item;
        });

        return $request->send();
    }

    public static function onSelectLocation($option, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $request = BotController::request('TextAnswerSelect', [
            BotController::request('Area/SelectLocation')->getText()->get(),
            $option['title']
        ]);
        $request->params->chatId = $chatId;
        $request->params->messageId = $messageId;
        $response = $request->send();

        $loc = RtuLocation::find($option['value']);
        $request = BotController::request('Area/SelectRtu');

        $request->params->chatId = $chatId;
        $request->setData('rtus', RtuList::getSnameOrderedByLocation($loc['id']));
        $request->setInKeyboard(function($item, $rtu) {
            $item['callback_data'] = encodeCallbackData(
                'rtu.select_rtu',
                $item['text'],
                $rtu['id']
            );
            return $item;
        });

        return $request->send();
    }

    public static function onSelectRtu($option, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $request = BotController::request('TextAnswerSelect', [
            BotController::request('Area/SelectRtu')->getText()->get(),
            $option['title']
        ]);
        $request->params->chatId = $chatId;
        $request->params->messageId = $messageId;
        $request->send();

        $rtu = RtuList::find($option['value']);
        return RtuController::sendRtuDetail($chatId, $rtu);
    }

    public static function sendRtuDetail($chatId, $rtu)
    {
        $request = BotController::request('Action/Typing');
        $request->params->chatId = $chatId;
        $request->send();

        $newosaseApi = new NewosaseApi();
        $newosaseApi->setupAuth();
        $data = $newosaseApi->sendRequest('GET', '/dashboard-service/operation/rtu/'.$rtu['id']);

        if(!$data) {
            $request = BotController::request('Error/TextErrorServer');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        if(!isset($data->result) || !$data->result) {
            $request = BotController::request('Error/TextErrorNotFound');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        $request = BotController::request('CheckRtu/TextRtuDetail');
        $request->setData('regional', Regional::find($rtu['regional_id']));
        $request->setData('witel', Witel::find($rtu['witel_id']));
        $request->setData('location', RtuLocation::find($rtu['location_id']));
        $request->setData('rtu', $data->result);
        $request->params->chatId = $chatId;
        $request->params->text = $request->getText()->get();
        
        $response = $request->send();
        if(!$response->isOk()) {
            return $response;
        }

        $request = BotController::request('Attachment/MapLocation', [
            $data->result->latitude,
            $data->result->longitude
        ]);
        $request->params->chatId = $chatId;
        $request->params->replyToMessageId = $response->getResult()->getMessageId();
        return $request->send();
    }
}