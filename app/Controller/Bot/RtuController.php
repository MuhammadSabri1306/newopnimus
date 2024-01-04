<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\ChatAction;

use App\Core\RequestData;
use App\BuiltMessageText\UserText;
use App\BuiltMessageText\PortText;
use App\Request\RequestInKeyboard;

use App\Core\CallbackData;
use App\ApiRequest\NewosaseApi;
use App\ApiRequest\NewosaseApiV2;
use App\Controller\BotController;
use App\Model\TelegramUser;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\RtuLocation;
use App\Model\RtuList;

useHelper('telegram-callback');

class RtuController extends BotController
{
    public static $callbacks = [
        'rtu.cekreg' => 'onSelectRegional',
        'rtu.cekwit' => 'onSelectWitel',
        'rtu.cekloc' => 'onSelectLocation',
        'rtu.cekrtu' => 'onSelectRtu',
    ];

    public static function checkRtu()
    {
        $message = RtuController::$command->getMessage();
        $fromId = $message->getFrom()->getId();
        $chatId = $message->getChat()->getId();
        $messageText = trim($message->getText(true));

        $telgUser = TelegramUser::findByChatId($chatId);
        if(!$telgUser) {
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
        
        if($telgUser['level'] == 'nasional') {

            $request = static::request('Area/SelectRegional');
            $request->setData('regionals', Regional::getSnameOrdered());

            $request->params->chatId = $chatId;
            $request->params->text = $request->getText()->newLine()
                ->addItalic('* Anda juga dapat memilih RTU dan Port dengan mengetikkan perintah')
                ->addItalic(' /cekrtu [Kode RTU], e.g: /cekrtu RTU00-D7-BAL')
                ->get();

            $callbackData = new CallbackData('rtu.cekreg');
            $callbackData->limitAccess($fromId);
            $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
                $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
                return $inKeyboardItem;
            });

            return $request->send();
            
        }
        
        if($telgUser['level'] == 'regional') {

            $request = static::request('Area/SelectWitel');
            $request->setData('witels', Witel::getNameOrdered($telgUser['regional_id']));

            $request->params->chatId = $chatId;
            $request->params->text = $request->getText()->newLine()
                ->addItalic('* Anda juga dapat memilih RTU dan Port dengan mengetikkan perintah')
                ->addItalic(' /cekrtu [Kode RTU], e.g: /cekrtu RTU00-D7-BAL')
                ->get();

            $callbackData = new CallbackData('rtu.cekwit');
            $callbackData->limitAccess($fromId);
            $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
                $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
                return $inKeyboardItem;
            });
            
            return $request->send();

        }
        
        $request = static::request('Area/SelectLocation');
        $request->setData('locations', RtuLocation::getSnameOrderedByWitel($telgUser['witel_id']));

        $request->params->chatId = $chatId;
        $request->params->text = $request->getText()->newLine()
                ->addItalic('* Anda juga dapat memilih RTU dan Port dengan mengetikkan perintah')
                ->addItalic(' /cekrtu [Kode RTU], e.g: /cekrtu RTU00-D7-BAL')
                ->get();

        $callbackData = new CallbackData('rtu.cekloc');
        $callbackData->limitAccess($fromId);
        $request->setInKeyboard(function($inKeyboardItem, $loc) use ($callbackData) {
            $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($loc['id']);
            return $inKeyboardItem;
        });
        
        return $request->send();
    }

    public static function onSelectRegional($regionalId, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $fromId = $callbackQuery->getFrom()->getId();
        $chatId = $message->getChat()->getId();
        $messageId = $message->getMessageId();

        static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

        $request = static::request('Area/SelectWitel');
        $request->setData('witels', Witel::getNameOrdered($regionalId));
        $request->params->chatId = $chatId;

        $callbackData = new CallbackData('rtu.cekwit');
        $callbackData->limitAccess($fromId);
        $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
            $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
            return $inKeyboardItem;
        });
        
        return $request->send();
    }

    public static function onSelectWitel($witelId, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $fromId = $callbackQuery->getFrom()->getId();
        $chatId = $message->getChat()->getId();
        $messageId = $message->getMessageId();

        static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

        $request = static::request('Area/SelectLocation');
        $request->setData('locations', RtuLocation::getSnameOrderedByWitel($witelId));
        $request->params->chatId = $chatId;

        $callbackData = new CallbackData('rtu.cekloc');
        $callbackData->limitAccess($fromId);
        $request->setInKeyboard(function($inKeyboardItem, $loc) use ($callbackData) {
            $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($loc['id']);
            return $inKeyboardItem;
        });
        
        return $request->send();
    }

    public static function onSelectLocation($locId, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $fromId = $callbackQuery->getFrom()->getId();
        $chatId = $message->getChat()->getId();
        $messageId = $message->getMessageId();

        static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

        $newosaseApi = new NewosaseApiV2();
        $newosaseApi->setupAuth();
        $newosaseApi->request['query'] = [ 'locationId' => $locId ];
        
        $osaseData = $newosaseApi->sendRequest('GET', '/dashboard-service/dashboard/rtu/port-sensors');
        if(!$osaseData->get()) {
            $request = static::request('Error/TextErrorServer');
            $request->params->chatId = $chatId;
            return $request->send();
        }
        
        $portList = $osaseData->get('result.payload');
        if(!is_array($portList)) {
            $request = static::request('Error/TextErrorNotFound');
            $request->params->chatId = $chatId;
            return $request->send();
        }
        
        $rtuSnames = array_reduce($portList, function($list, $port) {
            if(isset($port->rtu_sname) && !in_array($port->rtu_sname, $list)) {
                array_push($list, $port->rtu_sname);
            }
            return $list;
        }, []);
        
        $request = static::request('Area/SelectRtu');
        $request->params->chatId = $chatId;
        $request->setRtus($rtuSnames);
        
        $callbackData = new CallbackData('rtu.cekrtu');
        $callbackData->limitAccess($fromId);
        $request->setInKeyboard(function($inKeyboardItem, $rtuSname) use ($callbackData) {
            $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($rtuSname);
            return $inKeyboardItem;
        });
        
        return $request->send();
    }

    public static function onSelectRtu($rtuSname, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $fromId = $callbackQuery->getFrom()->getId();
        $chatId = $message->getChat()->getId();
        $messageId = $message->getMessageId();

        static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

        $rtu = RtuList::findBySname($rtuSname);
        return RtuController::sendRtuDetail($chatId, $rtu);
    }

    public static function sendRtuDetail($chatId, $rtu)
    {
        $request = static::request('Action/Typing');
        $request->params->chatId = $chatId;
        $request->send();

        $newosaseApi = new NewosaseApiV2();
        $newosaseApi->setupAuth();
        $newosaseApi->request['query'] = [ 'locationId' => $locId ];

        $rtuId = $rtu['uuid'] ?? $rtu['id'];
        $osaseData = $newosaseApi->sendRequest('GET', "/dashboard-service/operation/rtu/$rtuId");
        if(!$osaseData->get()) {
            $request = static::request('Error/TextErrorServer');
            $request->params->chatId = $chatId;
            return $request->send();
        }
        
        $rtuData = $osaseData->get('result');
        if(!$rtuData) {
            $request = static::request('Error/TextErrorNotFound');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        $request = BotController::request('CheckRtu/TextRtuDetail');
        $request->setData('regional', Regional::find($rtu['regional_id']));
        $request->setData('witel', Witel::find($rtu['witel_id']));
        $request->setData('location', RtuLocation::find($rtu['location_id']));
        $request->setData('rtu', $rtuData);
        $request->params->chatId = $chatId;
        $request->params->text = $request->getText()->get();
        
        $response = $request->send();
        $rtuLat = $osaseData->get('result.latitude');
        $rtuLng = $osaseData->get('result.longitude');

        if(!$response->isOk() || !$rtuLat || !$rtuLng) {
            return $response;
        }

        $detailMessageId = $response->getResult()->getMessageId();

        $request = BotController::request('Attachment/MapLocation', [ $rtuLat, $rtuLng ]);
        $request->params->chatId = $chatId;
        if($detailMessageId) {
            $request->params->replyToMessageId = $detailMessageId;
        }
        return $request->send();
    }
}