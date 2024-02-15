<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;

use App\Core\RequestData;
use App\Core\TelegramText;
use App\Core\Conversation;
use App\Core\CallbackData;

use App\Controller\BotController;
use App\Controller\Bot\AdminController;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\PicLocation;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\Datel;
use App\Model\RtuLocation;
use App\Model\Registration;

use App\BuiltMessageText\UserText;
use App\BuiltMessageText\PicText;
use App\Request\RequestInKeyboard;
use App\Request\RequestPic;

useHelper('array');

class PicController extends BotController
{
    public static $maxLocations = 7;
    public static $callbacks = [
        'pic.set_start' => 'onSetStart',
        'pic.select_regional' => 'onSetRegional',
        'pic.select_witel' => 'onSetWitel',
        'pic.add_location' => 'onAddLocation',
        'pic.update_loc' => 'onUpdateLocation',
        'pic.remove_loc' => 'onRemoveLocation',
        'pic.set_reset' => 'onReset',
        'pic.listtreg' => 'onListSelectRegional',
        'pic.listwit' => 'onListSelectWitel',
    ];

    public static function getPicRegistConversation($isRequired = false, $chatId = null, $fromId = null)
    {
        $conversation = static::getConversation('regist_pic', $chatId, $fromId);

        if($isRequired && !$conversation->isExists()) {
            $request = static::request('TextDefault');
            $request->setTarget( static::getRequestTarget() );
            $request->setText(function($text) {
                return $text->addText('Sesi anda telah berakhir. Mohon untuk melakukan permintaan')
                    ->addText(' ulang dengan mengetikkan perintah /setpic.');
            });
            $request->send();
            return null;
        }

        return $conversation;
    }

    public static function addLocation()
    {
        $conversation = static::getPicRegistConversation(true);
        if(!$conversation) return static::sendEmptyResponse();

        if(static::getUser()['level'] == 'nasional') {

            $request = static::request('Area/SelectRegional');
            $request->setTarget( static::getRequestTarget() );
            $request->setRegionals( Regional::getSnameOrdered() );
            $request->setInKeyboard(function($inKeyboardItem, $regional) {
                $inKeyboardItem['callback_data'] = 'pic.select_regional.'.$regional['id'];
                return $inKeyboardItem;
            });
            return $request->send();

        }

        if(static::getUser()['level'] == 'regional') {

            $request = static::request('Area/SelectWitel');
            $request->setTarget( static::getRequestTarget() );
            $request->setWitels( Witel::getNameOrdered(static::getUser()['regional_id']) );
            $request->setInKeyboard(function($inKeyboardItem, $witel) {
                $inKeyboardItem['callback_data'] = 'pic.select_witel.'.$witel['id'];
                return $inKeyboardItem;
            });
            return $request->send();

        }

        $request = static::request('Area/SelectLocation');
        $request->setTarget( static::getRequestTarget() );

        $locIds = $conversation->locations;
        $picLocs = RtuLocation::getSnameOrderedByWitel(static::getUser()['witel_id']);
        if(count($locIds) > 0) {
            $picLocs = array_filter($picLocs, function($loc) use ($locIds) {
                return !in_array($loc['id'], $locIds);
            });
        }
        $request->setLocations($picLocs);

        $request->setInKeyboard(function($inKeyboardItem, $loc) {
            $inKeyboardItem['callback_data'] = 'pic.add_location.'.$loc['id'];
            return $inKeyboardItem;
        });
        return $request->send();
    }

    public static function askLocations()
    {
        $conversation = static::getPicRegistConversation(true);
        if(!$conversation) return static::sendEmptyResponse();
        if(count($conversation->locations) < 1) {
            return static::addLocation();
        }

        $request = static::request('RegistPic/SelectUpdateLoc');
        $request->setTarget( static::getRequestTarget() );

        $picLocs = RtuLocation::getByIds($conversation->locations);
        $request->setLocations($picLocs, static::$maxLocations);
        $request->setInKeyboard(function($inKeyboard) {
            $inKeyboard['next']['callback_data'] = 'pic.update_loc.next';
            $inKeyboard['add']['callback_data'] = 'pic.update_loc.add';
            $inKeyboard['remove']['callback_data'] = 'pic.update_loc.remove';
            return $inKeyboard;
        });
        return $request->send();
    }

    public static function register()
    {
        $message = static::getMessage();
        $chatId = $message->getChat()->getId();

        if(!$message->getChat()->isPrivateChat()) {
            
            $request = static::request('Pic/TextErrorNotInPrivate');
            $request->setTarget( static::getRequestTarget() );
            return $request->send();

        }

        if(!static::getUser()) {

            $request = static::request('Error/TextUserUnidentified');
            $request->setTarget( static::getRequestTarget() );
            return $request->send();

        }

        $registration = Registration::query(function($db, $table) use ($chatId) {
            $query = "SELECT * FROM $table WHERE request_type='pic' AND status='unprocessed' AND chat_id=%i";
            $data = $db->queryFirstRow($query, $chatId);
            if(isset($data['data'])) $data['data'] = json_decode($data['data'], true);
            return $data ?? null;
        });

        if($registration) {
            
            $request = static::request('Registration/TextPicUpdateOnReview');
            $request->setTarget( static::getRequestTarget() );

            $locations = RtuLocation::getByIds($registration['data']['locations']);
            $request->setLocations($locations);
            
            $telgUserId = $registration['data']['telegram_user_id'];
            $telgPersUser = TelegramPersonalUser::findByUserId($telgUserId);
            $request->setTelegramPersonalUser($telgPersUser);

            return $request->send();

        }

        $telgUser = static::getUser();
        $conversation = static::getPicRegistConversation();
        
        if($conversation->isExists()) {
            
            $conversationStep = $conversation->getStep();
            if($conversationStep == 1 && is_array($conversation->locations)) {
                return static::askLocations();
            }

            if($conversationStep > 1) {
                return static::sendRegistRequest();
            }

        }

        $request = static::request('RegistPic/SelectTouApproval');
        $request->setTarget( static::getRequestTarget() );
        $request->setUser( static::getUser() );
        $request->setInKeyboard(function($inkeyboardData) {
            $inkeyboardData['agree']['callback_data'] = 'pic.set_start.continue';
            $inkeyboardData['disagree']['callback_data'] = 'pic.set_start.cancel';
            return $inkeyboardData;
        });
        return $request->send();
    }

    public static function reset()
    {
        $message = PicController::$command->getMessage();
        $chatId = $message->getChat()->getId();

        if(!$message->getChat()->isPrivateChat()) {
            $replyText = PicText::picAbortInGroup()->get();
            return PicController::$command->replyToChat($replyText);
        }

        $conversation = static::getPicRegistConversation();
        if($conversation->isExists()) {
            $conversation->cancel();
        }

        $telgUser = TelegramUser::findByChatId($chatId);
        if(!$telgUser) {
            $request = static::request('Error/TextUserUnidentified');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        if(!$telgUser['is_pic']) {
            $request = static::request('TextDefault');
            $request->params->chatId = $chatId;
            $request->setText(fn($text) => $text->addText('Anda belum terdaftar sebagai PIC.'));
            return $request->send();
        }


        $request = BotController::getRequest('Registration/PicReset', [ $chatId, $telgUser ]);
        $request->setRequest(function($inkeyboardData) {
            $inkeyboardData['continue']['callback_data'] = 'pic.set_reset.continue';
            $inkeyboardData['cancel']['callback_data'] = 'pic.set_reset.cancel';
            return $inkeyboardData;
        });
        return $request->send();
    }

    public static function onSetStart($callbackValue, $callbackQuery)
    {
        $message = static::getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();
        $user = $callbackQuery->getFrom();

        $request = static::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();

        if($callbackValue == 'cancel') {

            $request = static::request('TextDefault');
            $request->setTarget( static::getRequestTarget() );
            $request->setText(fn($text) => $text->addText('Proses registrasi dibatalkan. Terima kasih.'));
            return $request->send();
        }

        $conversation = static::getPicRegistConversation();
        if(!$conversation->isExists()) {
            $conversation->create();
            $conversation->locations = [];
        }

        $telgUser = static::getUser();
        $conversation->hasRegist = true;
        $conversation->telegramUserId = $telgUser['id'];

        if($telgUser['is_pic']) {
            $conversation->locations = array_column($telgUser['locations'], 'location_id');
            $conversation->commit();
        }

        if($telgUser['level'] == 'nasional') {

            $conversation->level = 'nasional';
            $conversation->commit();

        } elseif($telgUser['level'] == 'regional') {

            $conversation->level = 'regional';
            $conversation->regionalId = $telgUser['regional_id'];
            $conversation->commit();

        } elseif($telgUser['level'] == 'witel') {
            
            $conversation->level = 'witel';
            $conversation->regionalId = $telgUser['regional_id'];
            $conversation->witelId = $telgUser['witel_id'];
            $conversation->commit();

        }
        
        $conversation->setStep(1);
        $conversation->commit();
        return static::askLocations();
    }

    public static function onSetRegional($regionalId, $callbackQuery)
    {
        $message = static::getMessage();
        $chatId = $message->getChat()->getId();
        $messageId = $message->getMessageId();

        $request = static::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();

        $conversation = static::getPicRegistConversation(true);
        if(!$conversation) return static::sendEmptyResponse();

        $conversation->regionalId = $regionalId;
        $conversation->commit();
        
        $request = static::request('Area/SelectWitel');
        $request->setTarget( static::getRequestTarget() );
        $request->setWitels( Witel::getNameOrdered($regionalId) );
        $request->setInKeyboard(function($inKeyboardItem, $witel) {
            $inKeyboardItem['callback_data'] = 'pic.select_witel.'.$witel['id'];
            return $inKeyboardItem;
        });
        return $request->send();
    }

    public static function onSetWitel($witelId, $callbackQuery)
    {
        $message = static::getMessage();
        $chatId = $message->getChat()->getId();
        $messageId = $message->getMessageId();

        $request = static::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();

        $conversation = static::getPicRegistConversation(true);
        if(!$conversation) return static::sendEmptyResponse();

        $conversation->witelId = $witelId;
        $conversation->commit();
        
        $request = static::request('Area/SelectLocation');
        $request->setTarget( static::getRequestTarget() );

        $locIds = $conversation->locations;
        $picLocs = RtuLocation::getSnameOrderedByWitel($witelId);
        if(count($locIds) > 0) {
            $picLocs = array_filter($picLocs, function($loc) use ($locIds) {
                return !in_array($loc['id'], $locIds);
            });
        }
        $request->setLocations($picLocs);

        $request->setInKeyboard(function($inKeyboardItem, $loc) {
            $inKeyboardItem['callback_data'] = 'pic.add_location.'.$loc['id'];
            return $inKeyboardItem;
        });
        return $request->send();
    }

    public static function onAddLocation($locId, $callbackQuery)
    {
        $message = static::getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $request = static::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();
        
        $conversation = static::getPicRegistConversation(true);
        if(!$conversation) return static::sendEmptyResponse();

        $conversation->arrayPush('locations', $locId);
        $conversation->commit();

        return static::askLocations();
    }
    
    public static function onRemoveLocation($selectedLocId, $callbackQuery)
    {
        $message = static::getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $request = static::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();
        
        $conversation = PicController::getPicRegistConversation(true);
        if(!$conversation) return static::sendEmptyResponse();

        $picLocIds = $conversation->locations;
        $removedLocsIndex = findArrayIndex($picLocIds, fn($remLocId) => $remLocId == $selectedLocId);
        if($removedLocsIndex >= 0) {
            array_splice($picLocIds, $removedLocsIndex, 1);
        }

        $conversation->locations = $picLocIds;
        $conversation->commit();
        return static::askLocations();
    }

    public static function onUpdateLocation($callbackValue, $callbackQuery)
    {
        $message = static::getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $conversation = static::getPicRegistConversation(true);
        if(!$conversation) return static::sendEmptyResponse();

        $request = static::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();


        if($callbackValue == 'add') {
            return static::addLocation();
        }
        
        if($callbackValue == 'remove') {

            $request = BotController::getRequest('Registration/PicRemoveLocation', [ $chatId, $conversation->locations ]);
            $request->setRequest(
                function($inkeyboardItem, $loc) {
                    $inkeyboardItem['callback_data'] = 'pic.remove_loc.'.$loc['id'];
                    return $inkeyboardItem;
                }
            );
            return $request->send();

        }
        
        if($callbackValue == 'next') {

            $conversation->setStep(2);
            $conversation->commit();
            return static::sendRegistRequest();

        }
    }

    public static function onReset($callbackValue, $callbackQuery)
    {
        return static::callModules('on-reset', [
            'callbackValue' => $callbackValue,
            'callbackQuery' => $callbackQuery
        ]);
    }

    private static function sendRegistRequest()
    {
        $conversation = static::getPicRegistConversation(true);
        if(!$conversation) return static::sendEmptyResponse();

        $registData = [];
        $registData['request_type'] = 'pic';
        $registData['data']['has_regist'] = true;
        $registData['data']['locations'] = $conversation->locations;
        $picLocs = RtuLocation::getByIds($conversation->locations);

        $telgUser = TelegramUser::find($conversation->telegramUserId);
        $telgPersUser = TelegramPersonalUser::findByUserId($conversation->telegramUserId);

        $registData['data']['telegram_user_id'] = $conversation->telegramUserId;
        $registData['chat_id'] = $telgUser['chat_id'];
        $registData['user_id'] = $telgUser['user_id'];
        $regist = Registration::create($registData);

        $request = static::request('Registration/TextPicUpdateOnReview');
        $request->setTarget( static::getRequestTarget() );
        $request->setTelegramPersonalUser($telgPersUser);
        $request->setLocations($picLocs);
        $response = $request->send();

        if(!$regist) {
            return $response;
        }

        $conversation->done();
        AdminController::whenRegistPic($regist['id']);
        return $response;
    }

    public static function whenRegistApproved($registId)
    {
        return static::callModules('when-regist-approved', [ 'registId' => $registId ]);
    }

    public static function whenRegistRejected($registId)
    {
        return static::callModules('when-regist-rejected', [ 'registId' => $registId ]);
    }

    public static function list()
    {
        $message = PicController::$command->getMessage();
        $chatId = $message->getChat()->getId();
        $fromId = $message->getFrom()->getId();

        $telgUser = TelegramUser::findByChatId($chatId);
        if(!$telgUser) {
            $request = static::request('Error/TextUserUnidentified');
            $request->params->chatId = $chatId;
            return $request->send();
        }

        if($telgUser['level'] == 'nasional') {

            $request = BotController::request('Area/SelectRegional');
            $request->params->chatId = $chatId;
            $request->setRegionals(Regional::getSnameOrdered());

            $callbackData = new CallbackData('pic.listtreg');
            $callbackData->limitAccess($fromId);
            $request->setInKeyboard(function($inKeyboardItem, $regional) use ($callbackData) {
                $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($regional['id']);
                return $inKeyboardItem;
            });

            return $request->send();

        }

        if($telgUser['level'] == 'regional') {

            $request = static::request('Area/SelectWitel');
            $request->params->chatId = $chatId;
            $request->setWitels(Witel::getNameOrdered($telgUser['regional_id']));

            $callbackData = new CallbackData('pic.listwit');
            $callbackData->limitAccess($fromId);
            $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
                $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
                return $inKeyboardItem;
            });

            return $request->send();

        }

        $witelId = $telgUser['witel_id'];
        $witel = Witel::find($witelId);
        $pics = RtuLocation::query(function($db, $table) use ($witelId) {
            $tableTelgUser = TelegramUser::$table;
            $tableTelgPersUser = TelegramPersonalUser::$table;
            $tablePic = PicLocation::$table;
            $tableDatel = Datel::$table;
            $query = 'SELECT user.user_id, user.username, user.first_name, user.last_name, pers.nama AS full_name, '.
                " loc.id AS location_id, loc.location_name, loc.location_sname FROM $table AS loc".
                " JOIN $tableDatel AS datel ON datel.id=loc.datel_id LEFT JOIN $tablePic AS pic ON pic.location_id=loc.id".
                " LEFT JOIN $tableTelgUser AS user ON user.id=pic.user_id LEFT JOIN $tableTelgPersUser AS pers".
                ' ON pers.user_id=user.id WHERE datel.witel_id=%i ORDER BY loc.location_sname, user.regist_id';
            return $db->query($query, $witelId) ?? [];
        });

        $request = static::request('Pic/TextListInWitel');
        $request->params->chatId = $chatId;
        $request->setWitel($witel);
        $request->setPics($pics);
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
        $request->params->chatId = $chatId;
        $request->setWitels(Witel::getNameOrdered($regionalId));

        $callbackData = new CallbackData('pic.listwit');
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

        $witel = Witel::find($witelId);
        $pics = RtuLocation::query(function($db, $table) use ($witelId) {
            $tableTelgUser = TelegramUser::$table;
            $tableTelgPersUser = TelegramPersonalUser::$table;
            $tablePic = PicLocation::$table;
            $tableDatel = Datel::$table;
            $query = 'SELECT user.user_id, user.username, user.first_name, user.last_name, pers.nama AS full_name, '.
                " loc.id AS location_id, loc.location_name, loc.location_sname FROM $table AS loc".
                " JOIN $tableDatel AS datel ON datel.id=loc.datel_id LEFT JOIN $tablePic AS pic ON pic.location_id=loc.id".
                " LEFT JOIN $tableTelgUser AS user ON user.id=pic.user_id LEFT JOIN $tableTelgPersUser AS pers".
                ' ON pers.user_id=user.id WHERE datel.witel_id=%i ORDER BY loc.location_sname, user.regist_id';
            return $db->query($query, $witelId) ?? [];
        });

        $request = static::request('Pic/TextListInWitel');
        $request->params->chatId = $chatId;
        $request->setWitel($witel);
        $request->setPics($pics);
        return $request->send();
    }
}