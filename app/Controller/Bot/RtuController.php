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

        'rtu.listreg' => 'onListSelectRegional',
        'rtu.listwit' => 'onListSelectWitel',
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

            $request = static::request('Action/Typing');
            $request->params->chatId = $chatId;
            $request->send();

            $requestNotFound = static::request('Error/TextErrorNotFound');
            $requestNotFound->params->chatId = $chatId;

            $rtuParams = RtuList::findBySname($rtuSname);
            if(!$rtuParams) {
                return $requestNotFound->send();
            }

            $newosaseApi = new NewosaseApiV2();
            $newosaseApi->setupAuth();
            $newosaseApi->request['query'] = [
                'isArea' => 'hide',
                'isChildren' => 'view',
                'level' => 4,
                'location' => $rtuParams['location_id'],
            ];

            $osaseData = $newosaseApi->sendRequest('GET', '/parameter-service/mapview');
            $rtus = $osaseData->get('result.0.witel.0.rtu');
            if(!is_array($rtus) || count($rtus) < 1) {
                return $requestNotFound->send();
            }

            $rtuId = null;
            for($i=0; $i<count($rtus); $i++) {
                if($rtus[$i]->rtu_sname == $rtuSname) {
                    $rtuId = $rtus[$i]->id_rtu;
                    $i = count($rtus);
                }
            }

            if(!$rtuId) {
                return $requestNotFound->send();
            }

            return RtuController::sendRtuDetail($chatId, $rtuId);

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
        $request = static::request('Action/Typing');
        $request->params->chatId = $chatId;
        $request->send();

        $newosaseApi = new NewosaseApiV2();
        $newosaseApi->setupAuth();
        $newosaseApi->request['query'] = [
            'isArea' => 'hide',
            'isChildren' => 'view',
            'level' => 4,
            'location' => $locId,
        ];

        $osaseData = $newosaseApi->sendRequest('GET', '/parameter-service/mapview');
        $rtus = $osaseData->get('result.0.witel.0.rtu');
        if(!is_array($rtus) || count($rtus) < 1) {
            $request = static::request('Error/TextErrorNotFound');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        $request = static::request('Area/SelectRtu');
        $request->params->chatId = $chatId;

        $rtuList = array_map(function($rtu) {
            return [ 'sname' => $rtu->rtu_sname, 'id' => $rtu->id_rtu ];
        }, $rtus);
        $request->setRtus($rtuList);
        
        $callbackData = new CallbackData('rtu.cekrtu');
        $callbackData->limitAccess($fromId);
        $request->setInKeyboard(function($inKeyboardItem, $rtu) use ($callbackData) {
            $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($rtu['id']);
            return $inKeyboardItem;
        });
        
        return $request->send();
    }

    public static function onSelectRtu($rtuId, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $fromId = $callbackQuery->getFrom()->getId();
        $chatId = $message->getChat()->getId();
        $messageId = $message->getMessageId();

        static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();
        return RtuController::sendRtuDetail($chatId, $rtuId);
    }

    public static function sendRtuDetail($chatId, $rtuId)
    {
        $request = static::request('Action/Typing');
        $request->params->chatId = $chatId;
        $request->send();

        $newosaseApi = new NewosaseApiV2();
        $newosaseApi->setupAuth();

        $osaseData = $newosaseApi->sendRequest('GET', "/dashboard-service/operation/rtu/$rtuId");
        if(!$osaseData->get()) {
            $request = static::request('Error/TextErrorServer');
            $request->params->chatId = $chatId;
            return $request->send();
        }
        
        $rtuData = $osaseData->get('result');
        $rtu = $rtuData ? RtuList::findBySname($rtuData->sname) : null;
        if(!$rtuData || !$rtu) {
            $request = static::request('Error/TextErrorNotFound');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        $request = static::request('CheckRtu/TextRtuDetail');
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

        $request = static::request('Attachment/MapLocation', [ $rtuLat, $rtuLng ]);
        $request->params->chatId = $chatId;
        if($detailMessageId) {
            $request->params->replyToMessageId = $detailMessageId;
        }
        return $request->send();
    }

    public static function sendRtuList($chatId, $witelId)
    {
        $request = static::request('Action/Typing');
        $request->params->chatId = $chatId;
        $request->send();

        $newosaseApi = new NewosaseApiV2();
        $newosaseApi->setupAuth();
        $newosaseApi->request['query'] = [
            'isChildren' => 'view',
            'isArea' => 'hide',
            'level' => 2,
            'witel' => $witelId,
        ];

        $osaseData = $newosaseApi->sendRequest('GET', '/parameter-service/mapview');
        if(!$osaseData->get()) {
            $request = static::request('Error/TextErrorServer');
            $request->params->chatId = $chatId;
            return $request->send();
        }
        
        $witelData = $osaseData->get('result');
        if(!$witelData) {
            $request = static::request('Error/TextErrorNotFound');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        $request = static::request('CheckRtu/TextWitelsRtuList');
        $request->params->chatId = $chatId;

        $witel = Witel::find($witelId);
        $regional = Regional::find($witel['regional_id']);
        $request->setWitelName($witel['witel_name'], $regional['name']);
        $request->setRtuOfWitel($witelData);

        return $request->send();
    }

    public static function listRtu()
    {
        $message = static::$command->getMessage();
        $fromId = $message->getFrom()->getId();
        $chatId = $message->getChat()->getId();

        $telgUser = TelegramUser::findByChatId($chatId);
        if(!$telgUser) {
            $request = BotController::request('Error/TextUserUnidentified');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        if($telgUser['level'] == 'witel') {
            return static::sendRtuList($chatId, $telgUser['witel_id']);
        }

        if($telgUser['level'] == 'regional') {

            $request = static::request('Area/SelectWitel');
            $request->params->chatId = $chatId;
            $request->setData('witels', Witel::getNameOrdered($telgUser['regional_id']));

            $callbackData = new CallbackData('rtu.listwit');
            $callbackData->limitAccess($fromId);
            $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
                $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
                return $inKeyboardItem;
            });
            
            return $request->send();

        }

        $request = static::request('Area/SelectRegional');
        $request->params->chatId = $chatId;
        $request->setData('regionals', Regional::getSnameOrdered());

        $callbackData = new CallbackData('rtu.listreg');
        $callbackData->limitAccess($fromId);
        $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
            $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
            return $inKeyboardItem;
        });

        return $request->send();
    }

    public static function onListSelectRegional($regionalId, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $fromId = $callbackQuery->getFrom()->getId();
        $chatId = $message->getChat()->getId();
        $messageId = $message->getMessageId();

        static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

        $request = static::request('Area/SelectWitel');
        $request->setData('witels', Witel::getNameOrdered($regionalId));
        $request->params->chatId = $chatId;

        $callbackData = new CallbackData('rtu.listwit');
        $callbackData->limitAccess($fromId);
        $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
            $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
            return $inKeyboardItem;
        });
        
        return $request->send();
    }

    public static function onListSelectWitel($witelId, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $fromId = $callbackQuery->getFrom()->getId();
        $chatId = $message->getChat()->getId();
        $messageId = $message->getMessageId();

        static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();
        return static::sendRtuList($chatId, $witelId);
    }
}