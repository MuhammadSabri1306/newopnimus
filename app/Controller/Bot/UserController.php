<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\InlineKeyboard;

use App\Core\DB;
use App\Core\RequestData;
use App\Core\TelegramText;
use App\Core\Conversation;
use App\Core\CallbackData;
use App\Controller\BotController;
use App\Controller\Bot\AdminController;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\PicLocation;
use App\Model\Registration;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\AlertUsers;
use App\BuiltMessageText\UserText;
use App\Core\Exception\TelegramResponseException;

useHelper('telegram-callback');

class UserController extends BotController
{
    public static $callbacks = [
        'user.aggrmnt' => 'onRegist',
        'user.lvl' => 'onSelectLevel',
        'user.treg' => 'onSelectRegional',
        'user.witl' => 'onSelectWitel',
        'user.orgn' => 'onSelectOrganik',
        'user.reset' => 'onRegistReset',
    ];

    public static function getRegistConversation()
    {
        if($command = UserController::$command) {
            if($command->getMessage()) {
                $chatId = UserController::$command->getMessage()->getChat()->getId();
                $userId = UserController::$command->getMessage()->getFrom()->getId();
                return new Conversation('regist', $userId, $chatId);
            } elseif($command->getCallbackQuery()) {
                $chatId = UserController::$command->getCallbackQuery()->getMessage()->getChat()->getId();
                $userId = UserController::$command->getCallbackQuery()->getFrom()->getId();
                return new Conversation('regist', $userId, $chatId);
            }
        }

        return null;
    }

    public static function checkRegistStatus()
    {
        $message = UserController::$command->getMessage();
        $chatType = $message->getChat()->getType();
        $chatId = $message->getChat()->getId();

        if(!TelegramUser::exists($chatId)) {

            $regist = Registration::query(function($db, $table) use ($chatId) {
                $query = "SELECT * FROM $table WHERE request_type='user' AND status='unprocessed' AND chat_id=%i";
                $data = $db->queryFirstRow($query, $chatId);
                if(isset($data['data'])) $data['data'] = json_decode($data['data'], true);
                return $data ?? null;
            });

            if(!$regist) return null;

            $request = BotController::request('Registration/TextOnReview');
            $request->params->chatId = $chatId;
            $request->setRegistration($regist);
            if($regist['data']['level'] == 'regional' || $regist['data']['level'] == 'witel') {
                $request->setRegional(Regional::find($regist['data']['regional_id']));
            }
            if($regist['data']['level'] == 'witel') {
                $request->setWitel(Witel::find($regist['data']['witel_id']));
            }

            return $request->send();

        }

        $fullName = ($chatType == 'group' || $chatType == 'supergroup') ? 'Grup '.$message->getChat()->getTitle()
            : $message->getFrom()->getFirstName().' '.$message->getFrom()->getLastName();
        
        $request = BotController::request('Registration/AnimationUserExists');
        $request->params->chatId = $chatId;
        $request->setName($fullName);
        return $request->send();
    }

    public static function tou()
    {
        $message = UserController::$command->getMessage();
        $userChatId = $message->getFrom()->getId();
        $chatType = $message->getChat()->getType();
        $chatId = $message->getChat()->getId();

        $reqData = New RequestData();
        
        $request1 = BotController::request('Registration/AnimationTou');
        $request1->params->chatId = $chatId;
        $request1->params->replyMarkup = Keyboard::remove(['selective' => true]);
        if($chatType != 'private') {
            // Force reply is applied by default so it can work with privacy on
            $request1->params->replyMarkup = Keyboard::forceReply(['selective' => true]);
        }

        $response = $request1->send();

        $request2 = BotController::request('Registration/TextTou');
        $request2->params->paste($request1->params->copy('parseMode', 'chatId', 'replyMarkup'));
        // $reqData2->params->replyToMessageId = $response->getResult()->getMessageId();
        $response = $request2->send();

        $request3 = BotController::request('Registration/SelectTouApproval');
        $request3->params->paste($request1->params->copy('parseMode', 'chatId'));
        // $reqData3->params->replyToMessageId = $response->getResult()->getMessageId();
        
        $callbackData = new CallbackData('user.aggrmnt');
        $callbackData->limitAccess($userChatId);
        $request3->setInKeyboard(function($inKeyboardItem) use ($callbackData) {
            $inKeyboardItem['approve']['callback_data'] = $callbackData->createEncodedData('agree');
            $inKeyboardItem['reject']['callback_data'] = $callbackData->createEncodedData('disagree');
            return $inKeyboardItem;
        });

        return $request3->send();
    }

    public static function resetRegistration()
    {
        $message = UserController::$command->getMessage();
        $userChatId = $message->getFrom()->getId();
        $chatId = $message->getChat()->getId();

        $currUser = TelegramUser::findByChatId($chatId);
        if(!$currUser) {
            
            $request = BotController::request('Error/TextUserUnidentified');
            $request->params->chatId = $chatId;
            return $request->send();

        }
        
        $request = BotController::request('Registration/SelectResetApproval');
        $request->params->chatId = $chatId;
        
        $request->setUser($currUser);
        if($currUser['level'] != 'nasional') {
            $request->setRegional(Regional::find($currUser['regional_id']));
        }
        if($currUser['level'] == 'witel' || $currUser['level'] == 'pic') {
            $request->setWitel(Witel::find($currUser['witel_id']));
        }

        $callbackData = new CallbackData('user.reset');
        $callbackData->limitAccess($userChatId);
        $request->setInKeyboard(function($inKeyboardData) use ($callbackData) {
            $inKeyboardData['yes']['callback_data'] = $callbackData->createEncodedData(1);
            $inKeyboardData['no']['callback_data'] = $callbackData->createEncodedData(0);
            return $inKeyboardData;
        });
        
        return $request->send();
    }

    public static function register()
    {
        $conversation = UserController::getRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $message = UserController::$command->getMessage();
        $isPrivateChat = $message->getChat()->isPrivateChat();
        $chatId = $message->getChat()->getId();
        $userChatId = $message->getFrom()->getId();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;
        $reqData->replyMarkup = $isPrivateChat ? Keyboard::remove(['selective' => true])
            : Keyboard::forceReply(['selective' => true]);

        if($conversation->getStep() == 0) {

            $request = BotController::request('Registration/SelectLevel');
            $request->params->chatId = $chatId;
            $request->params->text = $request->getText()
                ->clear()
                ->addText('Proses registrasi dimulai. Silahkan memilih ')->startBold()->addText('Level Monitoring')->endBold()->addText('.')->newLine(2)
                ->startItalic()->addText('* Pilih Witel Apabila anda Petugas CME/Teknisi di Lokasi Tertentu')->endItalic()
                ->get();

            $callbackData = new CallbackData('user.lvl');
            $callbackData->limitAccess($userChatId);
            $request->setInKeyboard(function($inKeyboardItem) use ($callbackData) {
                $inKeyboardItem['nasional']['callback_data'] = $callbackData->createEncodedData('nasional');
                $inKeyboardItem['regional']['callback_data'] = $callbackData->createEncodedData('regional');
                $inKeyboardItem['witel']['callback_data'] = $callbackData->createEncodedData('witel');
                return $inKeyboardItem;
            });
            
            return $request->send();

        }

        $messageText = trim($message->getText(true));

        if($conversation->getStep() == 1) {

            if(empty($messageText)) {
                $reqData1 = $reqData->duplicate('parseMode', 'chatId', 'replyMarkup');
                $reqData1->text = $isPrivateChat ? 'Silahkan ketikkan nama lengkap anda.'
                    : "Silahkan ketikkan deskripsi grup $conversation->username.";

                return Request::sendMessage($reqData1->build());
            }

            if($isPrivateChat) {
                $conversation->fullName = $messageText;
            } else {
                $conversation->groupDescription = $messageText;
            }

            $conversation->nextStep();
            $conversation->commit();
            $messageText = '';

        }

        // Request::sendMessage([
        //     'chat_id' => 1931357638,
        //     'text' => $conversation->toJson()
        // ]);

        if(!$isPrivateChat && $conversation->getStep() > 1) {
            if($conversation->getStep() == 2) {
                return UserController::saveRegistFromConversation();
            }
            return Request::emptyResponse();
        }

        if($conversation->getStep() == 2) {

            if(!$message->getContact()) {
                $reqData2 = $reqData->duplicate('parseMode', 'chatId');
                $reqData2->text = 'Silahkan pilih menu "Bagikan Kontak Saya".';
                
                $keyboardButton = new KeyboardButton('Bagikan Kontak Saya');
                $keyboardButton->setRequestContact(true);
                $reqData2->replyMarkup = ( new Keyboard($keyboardButton) )
                        ->setOneTimeKeyboard(true)
                        ->setResizeKeyboard(true)
                        ->setSelective(true);

                return Request::sendMessage($reqData2->build());
            }

            $conversation->telp = $message->getContact()->getPhoneNumber();
            $conversation->nextStep();
            $conversation->commit();
            $messageText = '';

        }

        if($conversation->getStep() == 3) {

            if(empty($messageText)) {
                $reqData3 = $reqData->duplicate('parseMode', 'chatId', 'replyMarkup');
                $reqData3->text = 'Silahkan ketikkan instansi anda.';
                return Request::sendMessage($reqData3->build());
            }
            
            $conversation->instansi = $messageText;
            $conversation->nextStep();
            $conversation->commit();
            $messageText = '';

        }

        if($conversation->getStep() == 4) {

            if(empty($messageText)) {
                $reqData4 = $reqData->duplicate('parseMode', 'chatId', 'replyMarkup');
                $reqData4->text = 'Silahkan ketikkan unit kerja anda.';
                return Request::sendMessage($reqData4->build());
            }
            
            $conversation->unit = $messageText;
            $conversation->nextStep();
            $conversation->commit();
            $messageText = '';

        }

        if($conversation->getStep() == 5) {

            $request = BotController::request('Registration/SelectIsOrganik');
            $request->params->chatId = $chatId;
            
            $callbackData = new CallbackData('user.orgn');
            $callbackData->limitAccess($userChatId);
            $request->setInKeyboard(function($inKeyboardData) use ($callbackData) {
                $inKeyboardData['yes']['callback_data'] = $callbackData->createEncodedData(1);
                $inKeyboardData['no']['callback_data'] = $callbackData->createEncodedData(0);
                return $inKeyboardData;
            });
            
            return $request->send();

        }

        if($conversation->getStep() == 6) {

            if(empty($messageText)) {
                $reqData6 = $reqData->duplicate('parseMode', 'chatId', 'replyMarkup');
                $reqData6->text = 'Silahkan ketikkan NIK anda.';
                return Request::sendMessage($reqData6->build());
            }
            
            $conversation->nik = $messageText;
            $conversation->nextStep();
            $conversation->commit();
            $messageText = '';

        }

        if($conversation->getStep() == 7) {

            return UserController::saveRegistFromConversation();

        }

        return Request::emptyResponse();
    }

    public static function onRegist($callbackValue, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();
        $user = $callbackQuery->getFrom();
        $userChatId = $user->getId();

        $request = BotController::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();

        if($callbackValue == 'disagree') {

            $request = BotController::request('Registration/TextTouReject');
            $request->params->chatId = $chatId;
            $response = $request->send();
            
            $conversation = UserController::getRegistConversation();
            if($conversation->isExists()) {
                $conversation->cancel();
            }

            return $response;

        }
        
        if($callbackValue == 'agree') {
            
            $conversation = UserController::getRegistConversation();
            if(!$conversation->isExists()) {
                
                $conversation->create();
                $conversation->userId = $user->getId();
                $conversation->chatId = $message->getChat()->getId();
                $conversation->type = $message->getChat()->getType();
                
                if(!$message->getChat()->isPrivateChat()) {
                    $conversation->username = $message->getChat()->getTitle();
                } else {
                    $conversation->username = $user->getUsername();
                    $conversation->firstName = $user->getFirstName();
                    $conversation->lastName = $user->getLastName();
                }
                $conversation->commit();

            }

            $request = BotController::request('Registration/SelectLevel');
            $request->params->chatId = $chatId;
            
            $callbackData = new CallbackData('user.lvl');
            $callbackData->limitAccess($userChatId);
            $request->setInKeyboard(function($inKeyboardItem) use ($callbackData) {
                $inKeyboardItem['nasional']['callback_data'] = $callbackData->createEncodedData('nasional');
                $inKeyboardItem['regional']['callback_data'] = $callbackData->createEncodedData('regional');
                $inKeyboardItem['witel']['callback_data'] = $callbackData->createEncodedData('witel');
                return $inKeyboardItem;
            });

            return $request->send();

        }

        return Request::emptyResponse();
    }

    public static function onSelectLevel($callbackData, $callbackQuery)
    {
        if(in_array($callbackData, ['nasional', 'regional', 'witel'])) {
            $conversation = UserController::getRegistConversation();

            if(!$conversation->isExists()) {
                return Request::emptyResponse();
            }

            $conversation->level = $callbackData;
            $conversation->commit();
        }
        
        $message = $callbackQuery->getMessage();
        $userChatId = $callbackQuery->getFrom()->getId();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;
        $reqData->messageId = $messageId;
        
        $request = BotController::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();

        if($conversation->level == 'nasional') {

            $conversation->nextStep();
            $conversation->commit();

            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            $reqData1->text = $message->getChat()->isPrivateChat() ? 'Silahkan ketikkan nama lengkap anda.'
                : "Silahkan ketikkan deskripsi grup $conversation->username.";

            return Request::sendMessage($reqData1->build());

        } elseif($conversation->level == 'regional' || $conversation->level == 'witel') {

            $request = BotController::request('Area/SelectRegional');
            $request->setRegionals(Regional::getSnameOrdered());
            $request->params->chatId = $chatId;
            $request->params->text = $request->getText()
                ->clear()
                ->addText('Silahkan pilih')
                ->addBold(' Regional ')
                ->addText('yang akan dimonitor.')
                ->get();

            $callbackData = new CallbackData('user.treg');
            $callbackData->limitAccess($userChatId);
            $request->setInKeyboard(function($inKeyboardItem, $regional) use ($callbackData) {
                $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($regional['id']);
                return $inKeyboardItem;
            });

            return $request->send();

        }
        
        $reqData1 = $reqData->duplicate('parseMode', 'chatId');
        $reqData1->text = json_encode(['data' => $callbackData]);
        return Request::sendMessage($reqData1->build());
    }

    public static function onSelectRegional($callbackData, $callbackQuery)
    {
        $conversation = UserController::getRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $conversation->regionalId = $callbackData;
        $conversation->commit();

        $message = $callbackQuery->getMessage();
        $userChatId = $callbackQuery->getFrom()->getId();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $request = BotController::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();

        if($conversation->level == 'regional') {
            
            $reqData = New RequestData();
            $reqData->parseMode = 'markdown';
            $reqData->chatId = $chatId;
            $reqData->text = $message->getChat()->isPrivateChat() ? 'Silahkan ketikkan nama lengkap anda.'
                : "Silahkan ketikkan deskripsi grup $conversation->username.";

            $conversation->nextStep();
            $conversation->commit();
            
            return Request::sendMessage($reqData->build());
        
        }

        if($conversation->level == 'witel') {

            $request = BotController::request('Area/SelectWitel');
            $request->setWitels(Witel::getNameOrdered($conversation->regionalId));

            $request->params->chatId = $chatId;
            $request->params->text = $request->getText()
                ->clear()
                ->addText('Silahkan pilih')
                ->addBold(' Witel ')
                ->addText('yang akan dimonitor.')
                ->get();

            $callbackData = new CallbackData('user.witl');
            $callbackData->limitAccess($userChatId);
            $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
                $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
                return $inKeyboardItem;
            });

            return $request->send();

        }

        return Request::emptyResponse();
    }

    public static function onSelectWitel($callbackData, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();
        $isPrivateChat = $message->getChat()->isPrivateChat();

        $request = BotController::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();

        $conversation = UserController::getRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $conversation->witelId = $callbackData;
        $conversation->commit();

        if($conversation->level == 'witel') {

            $conversation->nextStep();
            $conversation->commit();
            
            $reqData = New RequestData();
            $reqData->parseMode = 'markdown';
            $reqData->chatId = $chatId;
            $reqData->text = $isPrivateChat ? 'Silahkan ketikkan nama lengkap anda.'
                : "Silahkan ketikkan deskripsi grup $conversation->username.";
            return Request::sendMessage($reqData->build());
        
        }

        return Request::emptyResponse();
    }

    public static function onSelectOrganik($callbackData, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $request = BotController::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();

        $conversation = UserController::getRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $conversation->isOrganik = $callbackData == 1;
        $conversation->nextStep();
        $conversation->commit();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;
        $reqData->text = 'Silahkan ketikkan NIK anda.';
        return Request::sendMessage($reqData->build());
    }

    public static function onRegistReset($callbackData, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $request = BotController::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $response = $request->send();

        if($callbackData != 1) {
            return $response;
        }

        $telegramUser = TelegramUser::findByChatId($chatId);
        if(!$telegramUser) {
            return Request::emptyResponse();
        }

        TelegramPersonalUser::deleteByUserId($telegramUser['id']);
        PicLocation::deleteByUserId($telegramUser['id']);
        AlertUsers::deleteByUserId($telegramUser['id']);

        TelegramUser::delete($telegramUser['id']);

        $request = BotController::request('TextDefault');
        $request->params->chatId = $chatId;
        $request->setText(function($text) {
            return $text->addText('Terimakasih User/Grup ini sudah tidak terdaftar di OPNIMUS lagi.')
                ->addText(' Anda dapat melakukan registrasi kembali untuk menggunakan bot ini lagi.');
        });
        return $request->send();
    }

    private static function saveRegistFromConversation()
    {
        $conversation = UserController::getRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $chatId = $conversation->chatId;

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;
        $reqData->replyMarkup = $conversation->type == 'private' ? Keyboard::remove(['selective' => true])
            : Keyboard::forceReply(['selective' => true]);

        $registration = Registration::query(function($db, $table) use ($chatId) {
            $query = "SELECT * FROM $table WHERE request_type='user' AND status='unprocessed' AND chat_id=%i";
            $data = $db->queryFirstRow($query, $chatId);
            if(isset($data['data'])) $data['data'] = json_decode($data['data'], true);
            return $data ?? null;
        });

        if($registration) {
            $reqData->text = ( UserText::getRegistSuccessText($conversation) )->get();
            return Request::sendMessage($reqData->build());
        }

        $registData = [];
        $registData['request_type'] = 'user';
        $registData['chat_id'] = $chatId;
        $registData['user_id'] = $conversation->userId;
        $registData['data']['username'] = $conversation->username;
        $registData['data']['type'] = $conversation->type;
        $registData['data']['is_pic'] = 0;
        $registData['data']['level'] = $conversation->level;
        
        if($conversation->level == 'regional' || $conversation->level == 'witel') {
            $registData['data']['regional_id'] = $conversation->regionalId;
        }

        if($conversation->level == 'witel') {
            $registData['data']['witel_id'] = $conversation->witelId;
        }

        if($conversation->type != 'private') {
            $registData['data']['group_description'] = $conversation->groupDescription;
        } else {
            $registData['data']['first_name'] = $conversation->firstName;
            $registData['data']['last_name'] = $conversation->lastName;
            $registData['data']['full_name'] = $conversation->fullName;
            $registData['data']['telp'] = $conversation->telp;
            $registData['data']['instansi'] = $conversation->instansi;
            $registData['data']['unit'] = $conversation->unit;
            $registData['data']['is_organik'] = $conversation->isOrganik;
            $registData['data']['nik'] = $conversation->nik;
        }

        $registration = Registration::create($registData);
        if(!$registration) {
            $reqData->text = 'Terdapat error saat akan menyimpan data anda. Silahkan coba beberapa saat lagi.';
            return Request::sendMessage($reqData->build());
        }

        $request = BotController::request('Registration/TextOnReview');
        $request->params->chatId = $chatId;
        $request->setRegistration($registration);
        if($conversation->level == 'regional' || $conversation->level == 'witel') {
            $request->setRegional(Regional::find($conversation->regionalId));
        }
        if($conversation->level == 'witel') {
            $request->setWitel(Witel::find($conversation->witelId));
        }

        $response = $request->send();
        AdminController::whenRegistUser($registration['id']);
        $conversation->done();
        return $response;
    }

    public static function whenRegistApproved($registId)
    {   
        return static::callModules('when-regist-approved', [ 'registId' => $registId ]);
    }

    public static function whenRegistRejected($registId)
    {
        $registData = Registration::find($registId);
        if(!$registData) {
            return Request::emptyResponse();
        }

        $request = BotController::request('Registration/TextUserRejected');
        $request->params->chatId = $registData['chat_id'];
        $request->setRejectedDate($registData['updated_at']);
        return $request->send();
    }
}