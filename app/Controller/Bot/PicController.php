<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;

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
use App\Model\Registration;

use App\BuiltMessageText\UserText;
use App\BuiltMessageText\PicText;
use App\Request\RequestInKeyboard;
use App\Request\RequestPic;

useHelper('array');

class PicController extends BotController
{
    protected static $callbacks = [
        'pic.set_start' => 'onSetStart',
        'pic.select_regional' => 'onSetRegional',
        'pic.select_witel' => 'onSetWitel',
        'pic.add_location' => 'onAddLocation',
        'pic.update_loc' => 'onUpdateLocation',
        'pic.remove_loc' => 'onRemoveLocation',
        'pic.set_organik' => 'onSelectOrganik',
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

    public static function askLocations($chatId, $locIds)
    {
        $request = BotController::getRequest('Registration/PicSetLocation', [ $chatId, $locIds ]);
        $request->setRequest(function($inkeyboardData) {
            $inkeyboardData['next']['callback_data'] = 'pic.update_loc.next';
            $inkeyboardData['add']['callback_data'] = 'pic.update_loc.add';
            $inkeyboardData['remove']['callback_data'] = 'pic.update_loc.remove';
            return $inkeyboardData;
        });
        
        return $request->send();
    }

    public static function askAgreement($chatId, $telgUser = null)
    {
        $isUnregisted = $telgUser ? false : true;
        if($isUnregisted) {

            $request = BotController::getRequest('Registration/Tou', [ $chatId, true ]);
            $request->setBtnApprovalRequest(function($inkeyboardData) {
                $inkeyboardData['agree']['callback_data'] = 'pic.set_start.continue';
                $inkeyboardData['disagree']['callback_data'] = 'pic.set_start.cancel';
                return $inkeyboardData;
            });

        } else {

            $request = BotController::getRequest('Registration/PicTou', [ $chatId, $telgUser ]);
            $request->setRequest(function($inkeyboardData) {
                $inkeyboardData['agree']['callback_data'] = 'pic.set_start.continue';
                $inkeyboardData['disagree']['callback_data'] = 'pic.set_start.cancel';
                return $inkeyboardData;
            });

        }

        return $request->send();
    }

    public static function askPersonal($message, $chatId)
    {
        $conversation = PicController::getPicRegistConversation();
        $messageText = trim($message->getText(true));

        $reqData = new RequestData();
        $reqData->chatId = $chatId;
        $reqData->parseMode = 'markdown';
        $reqData->replyMarkup = Keyboard::remove(['selective' => true]);

        if($conversation->getStep() == 2) {
            if(empty($messageText)) {
                $reqData = $reqData->duplicate('parseMode', 'chatId', 'replyMarkup');
                $reqData->text = 'Silahkan ketikkan nama lengkap anda.';
                return Request::sendMessage($reqData->build());
            }

            $conversation->fullName = $messageText;
            $conversation->nextStep();
            $conversation->commit();
            $messageText = '';
        }

        if($conversation->getStep() == 3) {
            if(!$message->getContact()) {
                $reqData->text = 'Silahkan pilih menu "Bagikan Kontak Saya".';
                
                $keyboardButton = new KeyboardButton('Bagikan Kontak Saya');
                $keyboardButton->setRequestContact(true);
                $reqData->replyMarkup = ( new Keyboard($keyboardButton) )
                        ->setOneTimeKeyboard(true)
                        ->setResizeKeyboard(true)
                        ->setSelective(true);

                return Request::sendMessage($reqData->build());
            }

            $conversation->telp = $message->getContact()->getPhoneNumber();
            $conversation->nextStep();
            $conversation->commit();
            $messageText = '';
        }

        if($conversation->getStep() == 4) {
            if(empty($messageText)) {
                $reqData->text = 'Silahkan ketikkan instansi anda.';
                return Request::sendMessage($reqData->build());
            }
            
            $conversation->instansi = $messageText;
            $conversation->nextStep();
            $conversation->commit();
            $messageText = '';
        }

        if($conversation->getStep() == 5) {
            if(empty($messageText)) {
                $reqData->text = 'Silahkan ketikkan unit kerja anda.';
                return Request::sendMessage($reqData->build());
            }
            
            $conversation->unit = $messageText;
            $conversation->nextStep();
            $conversation->commit();
            $messageText = '';
        }

        if($conversation->getStep() == 6) {
            $reqData->text = 'Apakah anda berstatus sebagai karyawan organik?';
            $reqData->replyMarkup = new InlineKeyboard([
                ['text' => 'Ya', 'callback_data' => 'pic.set_organik.ya'],
                ['text' => 'Tidak', 'callback_data' => 'pic.set_organik.tidak']
            ]);
            return Request::sendMessage($reqData->build());
        }

        if($conversation->getStep() == 7) {
            if(empty($messageText)) {
                $reqData->text = 'Silahkan ketikkan NIK anda.';
                return Request::sendMessage($reqData->build());
            }

            $conversation->nik = $messageText;
            $conversation->nextStep();
            $conversation->commit();
            $messageText = '';
        }
    }

    public static function register()
    {
        $message = PicController::$command->getMessage();
        $chatId = $message->getChat()->getId();

        if(!$message->getChat()->isPrivateChat()) {
            $replyText = PicText::picAbortInGroup()->get();
            return PicController::$command->replyToChat($replyText);
        }

        $registration = Registration::findUnprocessedByChatId($chatId);
        if($registration) {

            $reqData = new RequestData();
            $reqData->parseMode = 'markdown';
            $reqData->chatId = $chatId;

            if($registration['data']['has_regist']) {
                $telgPersUser = TelegramPersonalUser::findByUserId($registration['data']['telegram_user_id']);
                $locations = RtuLocation::getByIds($registration['data']['locations']);
                $reqData->text = PicText::registSuccess($telgPersUser, $locations)->get();
            } else {
                $reqData->text = UserText::registPicSuccess($registration, $picLocs)->get();
            }
            
            return Request::sendMessage($reqData->build());

        }

        $telgUser = TelegramUser::findByChatId($chatId);
        $conversation = PicController::getPicRegistConversation();

        if($conversation->isExists()) {
            
            $conversationStep = $conversation->getStep();
            if($conversationStep == 1) {
                return PicController::askLocations($chatId, $conversation->locations);
            }

            if($conversationStep > 1 && $telgUser) {
                return PicController::sendRegistRequest($chatId);
            }

            if($conversationStep > 1 && $conversationStep < 8) {
                return PicController::askPersonal($message, $chatId);
            }

            return PicController::sendRegistRequest($chatId);
        }

        return PicController::askAgreement($chatId, $telgUser);
    }

    public static function onSetStart($callbackValue, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $chatId = $message->getChat()->getId();
        $user = $callbackQuery->getFrom();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;
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

        $conversation->hasRegist = $telgUser ? true : false;
        if(!$telgUser) {

            $conversation->chatId = $message->getChat()->getId();
            $conversation->userId = $user->getId();
            $conversation->username = $user->getUsername();
            $conversation->type = $message->getChat()->getType();
            $conversation->firstName = $user->getFirstName();
            $conversation->lastName = $user->getLastName();
            
        } else {

            $conversation->telegramUserId = $telgUser['id'];

        }

        if($telgUser['level'] == 'nasional') {

            $conversation->level = 'nasional';
            $conversation->commit();

            $request = BotController::getRequest('Area/SelectRegional', [ $chatId, $telgUser['regional_id'] ]);
            $request->setRequest(function($inkeyboardItem, $regional) {
                $inkeyboardItem['callback_data'] = 'pic.select_regional.'.$regional['id'];
                return $inkeyboardItem;
            });

            $response = $request->send();

        } elseif($telgUser['level'] == 'regional') {

            $conversation->level = 'regional';
            $conversation->regionalId = $telgUser['regional_id'];
            $conversation->commit();

            $request = BotController::getRequest('Area/SelectWitel', [ $chatId, $telgUser['regional_id'] ]);
            $request->setRequest(function($inkeyboardItem, $witel) {
                $inkeyboardItem['callback_data'] = 'pic.select_witel.'.$witel['id'];
                return $inkeyboardItem;
            });

            $response = $request->send();

        } elseif($telgUser['level'] == 'witel') {
            
            $conversation->level = 'witel';
            $conversation->regionalId = $telgUser['regional_id'];
            $conversation->witelId = $telgUser['witel_id'];
            $conversation->nextStep();
            $conversation->commit();

            $request = BotController::getRequest('Area/SelectLocation', [ $chatId, $conversation->witelId ]);
            $request->setRequest(function($inkeyboardItem, $loc) {
                $inkeyboardItem['callback_data'] = 'pic.add_location.'.$loc['id'];
                return $inkeyboardItem;
            });

            $response = $request->send();
        }

        if($response->isOk()) {
            return $response;
        }
        return BotController::sendDebugMessage($response);
    }

    public static function onSetRegional($selectedRegId, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $chatId = $message->getChat()->getId();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;
        $reqData->messageId = $message->getMessageId();

        $regional = Regional::find($selectedRegId);
        $reqData->text = TelegramText::create('Silahkan pilih Regional.')->newLine(2)
            ->startBold()->addText('=> ')->endBold()
            ->addText($regional['name'])
            ->get();

        $request = Request::editMessageText($reqData->build());
        
        $conversation = PicController::getPicRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyMessage();
        }
        
        $conversation->regionalId = $regional['id'];
        $conversation->nextStep();
        $conversation->commit();
        
        $request = BotController::getRequest('Area/SelectWitel', [ $chatId, $conversation->regionalId ]);
        $request->setRequest(function($inkeyboardItem, $witel) {
            $inkeyboardItem['callback_data'] = 'pic.select_witel.'.$witel['id'];
            return $inkeyboardItem;
        });
        
        return $request->send();
    }

    public static function onSetWitel($selectedWitelId, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $chatId = $message->getChat()->getId();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;
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

        $request = BotController::getRequest('Area/SelectLocation', [ $chatId, $conversation->witelId ]);
        $request->setRequest(function($inkeyboardItem, $loc) {
            $inkeyboardItem['callback_data'] = 'pic.add_location.'.$loc['id'];
            return $inkeyboardItem;
        });
        
        return $request->send();
    }

    public static function onAddLocation($selectedLocId, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $chatId = $message->getChat()->getId();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;
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

        $response = PicController::askLocations($chatId, $conversation->locations);
        if($response->isOk()) {
            return $response;
        }
        return BotController::sendDebugMessage($response);
    }
    
    public static function onRemoveLocation($selectedLocId, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $chatId = $message->getChat()->getId();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;
        $reqData->messageId = $message->getMessageId();

        $location = RtuLocation::find($selectedLocId);
        $reqData->text = TelegramText::create('Silahkan pilih Lokasi yang ingin dihapus.')->newLine(2)
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

        $response = PicController::askLocations($chatId, $conversation->locations);
        if($response->isOk()) {
            return $response;
        }
        return BotController::sendDebugMessage($response);
    }

    public static function onUpdateLocation($callbackValue, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $chatId = $message->getChat()->getId();

        $conversation = PicController::getPicRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyMessage();
        }
        $picLocs = RtuLocation::getByIds($conversation->locations);

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;
        $reqData->messageId = $message->getMessageId();

        $srcRequest = BotController::getRequest('Registration/PicSetLocation', [ $chatId, $conversation->locations ]);
        $btnText = $callbackValue == 'next' ? 'Lanjutkan'
            : ($callbackValue == 'add' ? 'Tambah' : 'Hapus');
        $reqData->text = $srcRequest->getText()->newLine(2)
            ->addBold('=> ')->addText($btnText)
            ->get();
            
        $request = Request::editMessageText($reqData->build());

        if($callbackValue == 'add') {

            $request = BotController::getRequest('Area/SelectLocation', [ $chatId, $conversation->witelId ]);
            $request->setRequest(function($inkeyboardItem, $loc) {
                $inkeyboardItem['callback_data'] = 'pic.add_location.'.$loc['id'];
                return $inkeyboardItem;
            });

            $response = $request->send();

        } elseif($callbackValue == 'remove') {

            $request = BotController::getRequest('Registration/PicRemoveLocation', [ $chatId, $conversation->locations ]);
            $request->setRequest(
                function($inkeyboardItem, $loc) {
                    $inkeyboardItem['callback_data'] = 'pic.remove_loc.'.$loc['id'];
                    return $inkeyboardItem;
                }
            );

            $response = $request->send();

        } elseif($callbackValue == 'next') {

            $conversation->nextStep();
            $conversation->commit();
            $response = PicController::sendRegistRequest($chatId);

        }

        if($response->isOk()) {
            return $response;
        }
        return BotController::sendDebugMessage($response);
    }

    public static function onSelectOrganik($callbackData, $callbackQuery)
    {
        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $user = $callbackQuery->getUser();

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();

        $updateText = TelegramText::create('Apakah anda berstatus sebagai karyawan organik?')->newLine(2)
            ->startBold()->addText('=> ')->endBold()->addText(ucfirst($callbackData));

        $reqData->text = $updateText->get();
        Request::editMessageText($reqData->build());

        $conversation = PicController::getRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $conversation->isOrganik = $callbackData == 'ya';
        $conversation->nextStep();
        $conversation->commit();

        $reqData1 = $reqData->duplicate('parseMode', 'chatId');
        $reqData1->text = 'Silahkan ketikkan NIK anda.';
        return Request::sendMessage($reqData1->build());
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

        $registData = [];
        $registData['request_type'] = 'pic';
        $registData['data']['has_regist'] = $conversation->hasRegist;
        $registData['data']['locations'] = $conversation->locations;
        $picLocs = RtuLocation::getByIds($conversation->locations);

        if(!$conversation->hasRegist) {

            $registData['chat_id'] = $conversation->chatId;
            $registData['user_id'] = $conversation->userId;
            $registData['data']['username'] = $conversation->username;
            $registData['data']['type'] = $conversation->type;
            $registData['data']['level'] = 'pic';
            $registData['data']['first_name'] = $conversation->firstName;
            $registData['data']['last_name'] = $conversation->lastName;
            $registData['data']['full_name'] = $conversation->fullName;
            $registData['data']['telp'] = $conversation->telp;
            $registData['data']['instansi'] = $conversation->instansi;
            $registData['data']['unit'] = $conversation->unit;
            $registData['data']['is_organik'] = $conversation->isOrganik;
            $registData['data']['nik'] = $conversation->nik;
            $registData['data']['regional_id'] = $conversation->regionalId;
            $registData['data']['witel_id'] = $conversation->witelId;

            $reqData->text = UserText::registPicSuccess($registData, $picLocs)->get();

        } else {

            $telgUser = TelegramUser::find($conversation->telegramUserId);
            $telgPersUser = TelegramPersonalUser::findByUserId($conversation->telegramUserId);

            $registData['data']['telegram_user_id'] = $conversation->telegramUserId;
            $registData['chat_id'] = $telgUser['chat_id'];
            $registData['user_id'] = $telgUser['user_id'];

            $reqData->text = PicText::registSuccess($telgPersUser, $picLocs)->get();
            
        }

        $registration = Registration::create($registData);
        if(!$registration) {
            $reqData->text = 'Terdapat error saat akan menyimpan data anda. Silahkan coba beberapa saat lagi.';
            return Request::sendMessage($reqData->build());
        }

        $response = Request::sendMessage($reqData->build());
        if(!$response->isOk()) {
            return BotController::sendDebugMessage($response);
        }
        AdminController::whenRegistPic($registration['id']);
        
        return $response;
    }

    public static function whenRegistApproved($telegramUser)
    {
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

    public static function whenRegistRejected($registData)
    {
        if(!$registData) {
            return Request::emptyResponse();
        }

        $conversation = new Conversation('regist_pic', null, $registData['chat_id']);
        if($conversation->isExists()) {
            $conversation->done();
        }

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $registData['chat_id'];

        $reqData->text = TelegramText::create()
            ->addBold('Pengajuan PIC ditolak.')->newLine()
            ->addItalic($registData['updated_at'])->newLine(2)
            ->addText('Mohon maaf, permintaan anda tidak mendapat persetujuan oleh Admin. ')
            ->addText('Anda dapat berkoordinasi dengan Admin lokal anda untuk mendapatkan informasi terkait.')->newLine()
            ->addText('Terima kasih.')
            ->get();

        return Request::sendMessage($reqData->build());
    }
}