<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;

use App\Core\RequestData;
use App\Core\TelegramText;
use App\Core\Conversation;

use App\Controller\BotController;
use App\Controller\Bot\AdminController;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\RtuLocation;
use App\BuiltMessageText\UserText;
use App\BuiltMessageText\PicText;
use App\Request\RequestInKeyboard;
use App\Request\RequestPic;

useHelper('array');

class PicController extends BotController
{
    protected static $callbacks = [
        'pic.set_start' => 'onSetStart',
        'pic.select_witel' => 'onSetWitel',
        'pic.add_location' => 'onAddLocation',
        'pic.update_loc' => 'onUpdateLocation',
        'pic.remove_loc' => 'onRemoveLocation',
    ];

    public static function getPicRegistConversation()
    {
        if($command = PicController::$command) {
            if($command->getMessage()) {
                $chatId = PicController::$command->getMessage()->getChat()->getId();
                $userId = PicController::$command->getMessage()->getFrom()->getId();
                return new Conversation('regist_pic', $userId, $chatId);
            } elseif($command->getCallbackQuery()) {
                $chatId = PicController::$command->getCallbackQuery()->getMessage()->getChat()->getId();
                $userId = PicController::$command->getCallbackQuery()->getFrom()->getId();
                return new Conversation('regist_pic', $userId, $chatId);
            }
        }

        return null;
    }

    public static function register()
    {
        $message = UserController::$command->getMessage();
        $isPrivateChat = $message->getChat()->isPrivateChat();
        $reqData = New RequestData();

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->replyMarkup = $isPrivateChat ? Keyboard::remove(['selective' => true])
            : Keyboard::forceReply(['selective' => true]);

        $telgUser = TelegramUser::findByChatId($reqData->chatId);
        $conversation = UserController::getRegistConversation();

        // if(!$conversation->isExists()) {
        //     if(!$telgUser) 
        // }

        if(!$telgUser) {
            $reqData->text = UserText::unregistedText()->get();
            return Request::sendMessage($reqData->build());
        }
    }

    public static function setLocations()
    {
        $message = PicController::$command->getMessage();
        if(!$message->getChat()->isPrivateChat()) {
            $replyText = PicText::picAbortInGroup()->get();
            return PicController::$command->replyToChat($replyText);
        }

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();

        $telgUser = TelegramUser::findByChatId($reqData->chatId);
        if(!$telgUser) {
            $reqData->text = UserText::unregistedText()->get();
            return Request::sendMessage($reqData->build());
        }
        
        $reqData->text = PicText::picStatus($telgUser)->get();
        $reqData->replyMarkup = new InlineKeyboard([
            ['text' => 'Lanjutkan', 'callback_data' => 'pic.set_start.continue'],
            ['text' => 'Batalkan', 'callback_data' => 'pic.set_start.cancel']
        ]);

        return Request::sendMessage($reqData->build());
    }

    public static function onSetStart($callbackValue, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $user = $callbackQuery->getFrom();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();

        $telgUser = TelegramUser::findByChatId($reqData->chatId);
        $reqData->text = PicText::picStatus($telgUser)->newLine(2)
            ->startBold()->addText('=> ')->endBold()
            ->addText($callbackValue == 'continue' ? 'Lanjutkan' : 'Batalkan')
            ->get();

        $request = Request::editMessageText($reqData->build());
        $reqData1 = $reqData->duplicate('parseMode', 'chatId');

        if($callbackValue == 'cancel') {
            $reqData1->text = 'Proses registrasi dibatalkan. Terima kasih.';
            return Request::sendMessage($reqData1->build());
        }
        
        $conversation = PicController::getPicRegistConversation();
        if(!$conversation->isExists()) {
            $conversation->create();
            $conversation->locations = [];
        }

        if(!$telgUser) {
            
            $conversation->userId = $user->getId();
            $conversation->chatId = $message->getChat()->getId();
            $conversation->type = $message->getChat()->getType();
            
        }

        $conversation->hasRegist = true;
        $conversation->telegramUserId = $telgUser['id'];

        if($telgUser['level'] == 'nasional') {

            $conversation->level = 'nasional';
            $conversation->commit();

            $reqData1->text = 'Silahkan pilih Regional.';
            $response = RequestInKeyboard::regionalList(
                $reqData1,
                fn($regional) => 'pic.select_regional.'.$regional['id']
            );

        } elseif($telgUser['level'] == 'regional') {

            $conversation->level = 'regional';
            $conversation->regionalId = $telgUser['regional_id'];
            $conversation->commit();

            $reqData1->text = 'Silahkan pilih Witel.';
            $response = RequestInKeyboard::witelList(
                $telgUser['regional_id'],
                $reqData1,
                fn($witel) => 'pic.select_witel.'.$witel['id']
            );

        } elseif($telgUser['level'] == 'witel') {
            
            $conversation->level = 'witel';
            $conversation->witelId = $telgUser['witel_id'];
            $conversation->nextStep();
            $conversation->commit();
            
            $reqData1->text = 'Silahkan pilih Lokasi.';
            $response = RequestInKeyboard::locationList(
                $telgUser['witel_id'],
                $reqData1,
                fn($loc) => 'pic.add_location.'.$loc['id']
            );
        }

        if($response->isOk()) {
            return $response;
        }
        return BotController::sendDebugMessage($response);
    }

    public static function onSetWitel($selectedWitelId, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();

        $witel = Witel::find($selectedWitelId);
        $reqData->text = TelegramText::create('Silahkan pilih Witel.')->newLine(2)
            ->startBold()->addText('=> ')->endBold()
            ->addText($witel['witel_name'])
            ->get();

        $request = Request::editMessageText($reqData->build());
        
        $conversation = PicController::getPicRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyMessage();
        }
        
        $conversation->witelId = $witel['id'];
        $conversation->nextStep();
        $conversation->commit();
        
        $reqData1 = $reqData->duplicate('parseMode', 'chatId');
        $reqData1->text = 'Silahkan pilih Lokasi.';
        $response = RequestInKeyboard::locationList(
            $witel['id'],
            $reqData1,
            fn($loc) => 'pic.add_location.'.$loc['id']
        );

        if($response->isOk()) {
            return $response;
        }
        return BotController::sendDebugMessage($response);
    }

    public static function onAddLocation($selectedLocId, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();

        $location = RtuLocation::find($selectedLocId);
        $reqData->text = TelegramText::create('Silahkan pilih Lokasi.')->newLine(2)
            ->startBold()->addText('=> ')->endBold()
            ->addText($location['location_sname'])
            ->get();

        $request = Request::editMessageText($reqData->build());
        
        $conversation = PicController::getPicRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyMessage();
        }

        $conversation->arrayPush('locations', $location['id']);
        $conversation->commit();
        
        $response = RequestPic::manageLocation(
            $conversation->locations,
            function($reqData1) use ($reqData) {
                $reqData1->chatId = $reqData->chatId;
                return $reqData1;
            },
            function($inKeyboard) {
                $inKeyboard['next']['callback_data'] = 'pic.update_loc.next';
                $inKeyboard['add']['callback_data'] = 'pic.update_loc.add';
                $inKeyboard['remove']['callback_data'] = 'pic.update_loc.remove';
                return $inKeyboard;
            }
        );

        if($response->isOk()) {
            return $response;
        }
        return BotController::sendDebugMessage($response);
    }
    
    public static function onRemoveLocation($selectedLocId, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();

        $location = RtuLocation::find($selectedLocId);
        $reqData->text = TelegramText::create('Silahkan pilih Lokasi yang ingin dihapus')->newLine(2)
            ->startBold()->addText('=> ')->endBold()
            ->addText($location['location_sname'])
            ->get();

        $request = Request::editMessageText($reqData->build());
        
        $conversation = PicController::getPicRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyMessage();
        }

        $picLocIds = $conversation->locations;
        $removedLocsIndex = findArrayIndex($picLocIds, fn($remLocId) => $remLocId == $selectedLocId);
        if($removedLocsIndex >= 0) {
            array_splice($picLocIds, $removedLocsIndex, 1);
        }

        $conversation->locations = $picLocIds;
        $conversation->commit();

        $response = RequestPic::manageLocation(
            $conversation->locations,
            function($reqData1) use ($reqData) {
                $reqData1->chatId = $reqData->chatId;
                return $reqData1;
            },
            function($inKeyboard) {
                $inKeyboard['next']['callback_data'] = 'pic.update_loc.next';
                $inKeyboard['add']['callback_data'] = 'pic.update_loc.add';
                $inKeyboard['remove']['callback_data'] = 'pic.update_loc.remove';
                return $inKeyboard;
            }
        );

        if($response->isOk()) {
            return $response;
        }
        return BotController::sendDebugMessage($response);
    }

    public static function onUpdateLocation($callbackValue, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $conversation = PicController::getPicRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyMessage();
        }
        $picLocs = RtuLocation::getByIds($conversation->locations);

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();

        $btnText = $callbackValue == 'next' ? 'Lanjutkan'
            : ($callbackValue == 'add' ? 'Tambah' : 'Hapus');
        $reqData->text = PicText::editSelectedLocation($picLocs)->newLine(2)
            ->addBold('=> ')->addText($btnText)
            ->get();
        $request = Request::editMessageText($reqData->build());

        if($callbackValue == 'add') {

            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            $reqData1->text = 'Silahkan pilih Lokasi.';
            $response = RequestInKeyboard::locationList(
                $conversation->witelId,
                $reqData1,
                fn($loc) => 'pic.add_location.'.$loc['id']
            );

        } elseif($callbackValue == 'remove') {


            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            $reqData1->text = 'Silahkan pilih Lokasi yang ingin dihapus.';
            $reqData1->replyMarkup = new InlineKeyboard(array_map(function($loc) {
                return [
                    'text' => '❌ '.$loc['location_sname'],
                    'callback_data' => 'pic.remove_loc.'.$loc['id']
                ];
            }, $picLocs));

            $response = Request::sendMessage($reqData1->build());

        } elseif($callbackValue == 'next') {

            $response = PicController::sendRegistRequest($reqData1->chatId);

        }

        if($response->isOk()) {
            return $response;
        }
        return BotController::sendDebugMessage($response);
    }

    private static function sendRegistRequest($chatId)
    {
        $conversation = PicController::getPicRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyMessage();
        }

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;
        $reqData->replyMarkup = Keyboard::remove(['selective' => true]);

        $registration = Registration::findUnprocessedByChatId($reqData->chatId);
        if($registration) {
            // $reqData->text = UserText::getRegistSuccessText($conversation)->get();
            // return Request::sendMessage($reqData->build());
            return Request::emptyMessage();
        }

        $registData = [];
        $registData['request_type'] = 'pic';
        $registData['locations'] = $conversation->locations;
        $registData['has_regist'] = $conversation->hasRegist;
        $picLocs = RtuLocation::getByIds($conversation->locations);

        if(!$conversation->hasRegist) {

            $regional = Regional::find($conversation->regionalId);
            $witel = Witel::find($conversation->witelId);

            $registData['chat_id'] = $conversation->chatId;
            $registData['user_id'] = $conversation->userId;
            $registData['data']['username'] = $conversation->username;
            $registData['data']['type'] = $conversation->type;
            $registData['data']['level'] = 'pic';
            $registData['data']['regional_id'] = $regional['id'];
            $registData['data']['regional_name'] = $regional['name'];
            $registData['data']['witel_id'] = $witel['id'];
            $registData['data']['witel_name'] = $witel['witel_name'];
            $registData['data']['first_name'] = $conversation->firstName;
            $registData['data']['last_name'] = $conversation->lastName;
            $registData['data']['full_name'] = $conversation->fullName;
            $registData['data']['telp'] = $conversation->telp;
            $registData['data']['instansi'] = $conversation->instansi;
            $registData['data']['unit'] = $conversation->unit;
            $registData['data']['is_organik'] = $conversation->isOrganik;
            $registData['data']['nik'] = $conversation->nik;

            $reqData->text = UserText::registPicSuccess($registData, $picLocs)->get();

        } else {

            $registData['data']['telegram_user_id'] = $conversation->telegramUserId;
            $registData['data']['regional_id'] = $regional['id'];
            $registData['data']['regional_name'] = $regional['name'];
            $registData['data']['witel_id'] = $witel['id'];
            $registData['data']['witel_name'] = $witel['witel_name'];

            $telgPersUser = TelegramPersonalUser::findByUserId($conversation->telegramUserId);
            $reqData->text = PicText::registSuccess($telgPersUser, $picLocs)->get();
            
        }

        $registration = Registration::create($registData);
        if(!$registration) {
            $reqData->text = 'Terdapat error saat akan menyimpan data anda. Silahkan coba beberapa saat lagi.';
            return Request::sendMessage($reqData->build());
        }

        $response = Request::sendMessage($reqData->build());
        AdminController::whenRegistPic($registration['id']);
        
        return $response;
    }

    public static function whenRegistApproved($userId)
    {
        $telegramUser = TelegramUser::find($userId);
        if(!$telegramUser) {
            return Request::emptyResponse();
        }

        $conversation = new Conversation('regist_pic', null, $telegramUser['chat_id']);
        if($conversation->isExists()) {
            $conversation->done();
        }

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $telegramUser['chat_id'];
        $reqData->text = PicText::getRegistApprovedText($telegramUser)->get();

        return Request::sendMessage($reqData->build());
    }
}