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
use App\Controller\BotController;
use App\Controller\Bot\AdminController;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\PicLocation;
use App\Model\Registration;
use App\Model\Regional;
use App\Model\Witel;
use App\BuiltMessageText\UserText;
use App\Core\Exception\TelegramResponseException;

useHelper('telegram-callback');

class UserController extends BotController
{
    public static $callbacks = [
        'user.regist_approval' => 'onRegist',
        'user.regist_level' => 'onSelectLevel',
        'user.select_regional' => 'onSelectRegional',
        'user.select_witel' => 'onSelectWitel',
        'user.regist_organik' => 'onSelectOrganik',
        'user.reset_approval' => 'onRegistReset',
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
            return null;
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
        $request3->setInKeyboard(function($inKeyboardItem) {
            $inKeyboardItem['approve']['callback_data'] = encodeCallbackData(
                'user.regist_approval',
                'Setuju',
                'agree'
            );
            $inKeyboardItem['reject']['callback_data'] = encodeCallbackData(
                'user.regist_approval',
                'Tidak',
                'disagree'
            );
            return $inKeyboardItem;
        });

        return $request3->send();
    }

    public static function resetRegistration()
    {
        $message = UserController::$command->getMessage();
        $reqData = New RequestData();

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->replyMarkup = Keyboard::remove(['selective' => true]);
        
        if($message->getChat()->isGroupChat() || $message->getChat()->isSuperGroup()) {
            $reqData->replyMarkup = Keyboard::forceReply(['selective' => true]);
        }

        $user = TelegramUser::findByChatId($reqData->chatId);
        if(!$user) {

            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            $chatType = $message->getChat()->getType();
            $fullName = ($chatType=='group' || $chatType=='supergroup') ? 'Grup '.$message->getChat()->getTitle()
                : $message->getFrom()->getFirstName().' '.$message->getFrom()->getLastName();
            $reqData1->animation = 'https://giphy.com/gifs/transformers-optimus-prime-transformer-transformers-rise-of-the-beasts-Bf3Anv7HuOPHEPkiOx';
            $reqData1->caption = "$fullName tidak terdaftar dalam OPNIMUS.";
            
            return Request::sendAnimation($reqData1->build());

        }
        $replyText = TelegramText::create('User/Grup Ini Akan reset dari Bot OPNIMUS dengan data:')
            ->newLine(2)
            ->startCode();

        if(!$user['regional_id']) {
            $replyText->addText('Level : NASIONAL')->newLine();
        }
        
        if($user['regional_id']) {
            $regional = Regional::find($user['regional_id']);
            $replyText->addText('🏢Regional: '.$regional['name'])->newLine();
        }

        if($user['witel_id']) {
            $witel = Witel::find($user['witel_id']);
            $replyText->addText('🌇Witel   : '.$witel['witel_name'])->newLine();
        }

        $replyText->endCode()->newLine()->addText('Dengan melakukan reset user, user/Grup ini akan berhenti menggunakan layanan OPNIMUS.');
        $reqData->text = $replyText->get();
        Request::sendMessage($reqData->build());

        $reqData1 = $reqData->duplicate('parseMode', 'chatId');
        $reqData1->text = TelegramText::create()
            ->addText('Apakah anda yakin untuk ')
            ->startBold()->addText('keluar/reset')->endBold()
            ->addText(' dari OPNIMUS?')
            ->get();
            
        $reqData1->replyMarkup = new InlineKeyboard([
            ['text' => '📵 Keluar', 'callback_data' => 'user.reset_approval.exit'],
            ['text' => '❎ Tidak', 'callback_data' => 'user.reset_approval.cancel']
        ]);

        return Request::sendMessage($reqData1->build());
    }

    public static function register()
    {
        $conversation = UserController::getRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $message = UserController::$command->getMessage();
        $isPrivateChat = $message->getChat()->isPrivateChat();
        $reqData = New RequestData();

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->replyMarkup = $isPrivateChat ? Keyboard::remove(['selective' => true])
            : Keyboard::forceReply(['selective' => true]);

        if($conversation->getStep() == 0) {

            $reqData->text = TelegramText::create()
                ->addText('Proses registrasi dimulai. Silahkan memilih ')->startBold()->addText('Level Monitoring')->endBold()->addText('.')->newLine(2)
                ->startItalic()->addText('* Pilih Witel Apabila anda Petugas CME/Teknisi di Lokasi Tertentu')->endItalic()
                ->get();
                
            $reqData->replyMarkup = new InlineKeyboard([
                ['text' => 'Nasional', 'callback_data' => 'user.regist_level.nasional'],
            ], [
                ['text' => 'Regional', 'callback_data' => 'user.regist_level.regional'],
                ['text' => 'Witel', 'callback_data' => 'user.regist_level.witel']
            ]);
            
            return Request::sendMessage($reqData->build());

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

            $reqData5 = $reqData->duplicate('parseMode', 'chatId');
            $reqData5->text = 'Apakah anda berstatus sebagai karyawan organik?';
                
            $reqData5->replyMarkup = new InlineKeyboard([
                ['text' => 'Ya', 'callback_data' => 'user.regist_organik.ya'],
                ['text' => 'Tidak', 'callback_data' => 'user.regist_organik.tidak']
            ]);
            
            return Request::sendMessage($reqData5->build());

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

    public static function onRegist($option, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $request = BotController::request('TextAnswerSelect', [
            BotController::request('Registration/SelectTouApproval')->getText()->get(),
            $option['title']
        ]);
        $request->params->chatId = $chatId;
        $request->params->messageId = $messageId;
        $request->send();

        $message = $callbackQuery->getMessage();
        $user = $callbackQuery->getFrom();

        if($option['value'] == 'disagree') {

            $request = BotController::request('Registration/TextTouReject');
            $request->params->chatId = $chatId;
            $response = $request->send();
            
            $conversation = UserController::getRegistConversation();
            if($conversation->isExists()) {
                $conversation->cancel();
            }

            return $response;

        }
        
        if($option['value'] == 'agree') {
            
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

            $request->setInKeyboard(function($inKeyboardItem) {
                $inKeyboardItem['nasional']['callback_data'] = 'user.regist_level.nasional';
                $inKeyboardItem['regional']['callback_data'] = 'user.regist_level.regional';
                $inKeyboardItem['witel']['callback_data'] = 'user.regist_level.witel';
                return $inKeyboardItem;
            });

            $response = $request->send();
            return $response;

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
        
        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $user = $callbackQuery->getUser();

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();

        $updateText = TelegramText::create()
            ->addText('Proses registrasi dimulai. Silahkan memilih ')->startBold()->addText('Level Monitoring')->endBold()->addText('.')->newLine(2)
            ->startItalic()->addText('* Pilih Witel Apabila anda Petugas CME/Teknisi di Lokasi Tertentu')->endItalic()
            ->newLine(2);

        if(!$message->getChat()->isPrivateChat()) {
            $updateText = $updateText->addText('User')->startBold()->addText(' > ')->endBold()->addText(ucfirst($callbackData));
        } else {
            $updateText = $updateText->startBold()->addText('=> ')->endBold()->addText(ucfirst($callbackData));
        }

        $reqData->text = $updateText->get();
        Request::editMessageText($reqData->build());

        if($conversation->level == 'nasional') {

            $conversation->nextStep();
            $conversation->commit();

            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            $reqData1->text = $message->getChat()->isPrivateChat() ? 'Silahkan ketikkan nama lengkap anda.'
                : "Silahkan ketikkan deskripsi grup $conversation->username.";

            return Request::sendMessage($reqData1->build());

        } elseif($conversation->level == 'regional' || $conversation->level == 'witel') {

            $replyText = TelegramText::create('Pilih Regional yang akan dimonitor.');
            if($conversation->level == 'regional') {
                $replyText->newLine()
                    ->startItalic()->addText('* Termasuk semua Witel')->endItalic();
            }

            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            $reqData1->text = $replyText->get();
            
            $regionals = Regional::getSnameOrdered();
            $inlineKeyboardData = array_map(function($regional) {
                return [
                    [
                        'text' => $regional['name'],
                        'callback_data' => 'user.select_regional.'.$regional['id']
                    ]
                ];
            }, $regionals);

            $reqData1->replyMarkup = new InlineKeyboard(...$inlineKeyboardData);
            return Request::sendMessage($reqData1->build());

        }
        
        $reqData1 = $reqData->duplicate('parseMode', 'chatId');
        $reqData1->text = json_encode(['data' => $callbackData]);
        return Request::sendMessage($reqData1->build());
    }

    public static function onSelectRegional($data, $callbackQuery)
    {
        $conversation = UserController::getRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $conversation->regionalId = $data;
        $conversation->commit();

        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $user = $callbackQuery->getUser();
        $regional = Regional::find($conversation->regionalId);

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();

        $updateText = TelegramText::create('Pilih Regional yang akan dimonitor.');
        if($conversation->level == 'regional') {
            $updateText->newLine()->startItalic()->addText('* Termasuk semua Witel')->endItalic();
        }

        $updateText->newLine(2);
        if(!$message->getChat()->isPrivateChat()) {
            $updateText->addText('User')->startBold()->addText(' > ')->endBold()->addText($regional['name']);
        } else {
            $updateText->startBold()->addText('=> ')->endBold()->addText($regional['name']);
        }

        $reqData->text = $updateText->get();
        Request::editMessageText($reqData->build());

        if($conversation->level == 'regional') {
            
            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            $reqData1->text = $message->getChat()->isPrivateChat() ? 'Silahkan ketikkan nama lengkap anda.'
                : "Silahkan ketikkan deskripsi grup $conversation->username.";

            $conversation->nextStep();
            $conversation->commit();
            
            return Request::sendMessage($reqData1->build());
        
        }

        if($conversation->level == 'witel') {

            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            $reqData1->text = 'Pilih Witel yang akan dimonitor.';
            
            $witels = Witel::getNameOrdered($conversation->regionalId);
            $inlineKeyboardData = array_map(function($witel) {
                return [
                    [
                        'text' => $witel['witel_name'],
                        'callback_data' => 'user.select_witel.'.$witel['id']
                    ]
                ];
            }, $witels);

            $reqData1->replyMarkup = new InlineKeyboard(...$inlineKeyboardData);
            return Request::sendMessage($reqData1->build());

        }

        return Request::emptyResponse();
    }

    public static function onSelectWitel($data, $callbackQuery)
    {
        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $user = $callbackQuery->getUser();
        $witel = Witel::find($data);
        $regional = Regional::find($witel['regional_id']);

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();

        $updateText = TelegramText::create('Pilih Witel yang akan dimonitor.')->newLine(2);

        if(!$message->getChat()->isPrivateChat()) {
            $updateText = $updateText->addText('User')->startBold()->addText(' > ')->endBold()->addText($witel['witel_name']);
        } else {
            $updateText = $updateText->startBold()->addText('=> ')->endBold()->addText($witel['witel_name']);
        }

        $reqData->text = $updateText->get();
        Request::editMessageText($reqData->build());

        $conversation = UserController::getRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $conversation->witelId = $data;
        $conversation->commit();

        if($conversation->level == 'witel') {
            
            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            $reqData1->text = $message->getChat()->isPrivateChat() ? 'Silahkan ketikkan nama lengkap anda.'
                : "Silahkan ketikkan deskripsi grup $conversation->username.";

            $conversation->nextStep();
            $conversation->commit();
            
            return Request::sendMessage($reqData1->build());
        
        }

        return Request::emptyResponse();
    }

    public static function onSelectOrganik($callbackData, $callbackQuery)
    {
        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $user = $callbackQuery->getUser();

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();

        $updateText = TelegramText::create('Apakah anda berstatus sebagai karyawan organik?')->newLine(2);

        if(!$message->getChat()->isPrivateChat()) {
            $updateText = $updateText->addText('User')->startBold()->addText(' > ')->endBold()->addText(ucfirst($callbackData));
        } else {
            $updateText = $updateText->startBold()->addText('=> ')->endBold()->addText(ucfirst($callbackData));
        }

        $reqData->text = $updateText->get();
        Request::editMessageText($reqData->build());

        $conversation = UserController::getRegistConversation();
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

    public static function onRegistReset($data, $callbackQuery)
    {
        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $user = $callbackQuery->getFrom();

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();

        $updateText = TelegramText::create($message->getText())->newLine(2);
        $dataText = $data == 'exit' ? 'Keluar' : 'Tidak';

        if(!$message->getChat()->isPrivateChat()) {
            $updateText = $updateText->addText('User')->startBold()->addText(' > ')->endBold()->addText($dataText);
        } else {
            $updateText = $updateText->startBold()->addText('=> ')->endBold()->addText($dataText);
        }

        $reqData->text = $updateText->get();
        $response = Request::editMessageText($reqData->build());
        if($data != 'exit') {
            return $response;
        }

        $telegramUser = TelegramUser::findByChatId($reqData->chatId);
        if(!$telegramUser) {
            return Request::emptyResponse();
        }

        if($telegramUser['type'] == 'private') {
            TelegramPersonalUser::deleteByUserId($telegramUser['id']);
        }
        
        if($telegramUser['is_pic']) {
            PicLocation::deleteByUserId($telegramUser['id']);
        }

        TelegramUser::delete($telegramUser['id']);

        $reqData1 = $reqData->duplicate('parseMode', 'chatId');
        $reqData1->text = 'Terimakasih User/Grup ini sudah tidak terdaftar di OPNIMUS lagi. Untuk menggunakan bot ini lagi, silahkan mendaftarkan lagi ke bot ini.';
        return Request::sendMessage($reqData1->build());
    }

    private static function saveRegistFromConversation()
    {
        $conversation = UserController::getRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $conversation->chatId;
        $reqData->replyMarkup = $conversation->type == 'private' ? Keyboard::remove(['selective' => true])
            : Keyboard::forceReply(['selective' => true]);

        $registration = Registration::findUnprocessedByChatId($conversation->chatId);
        if($registration) {
            $reqData->text = ( UserText::getRegistSuccessText($conversation) )->get();
            return Request::sendMessage($reqData->build());
        }

        $registData = [];
        $registData['request_type'] = 'user';
        $registData['chat_id'] = $conversation->chatId;
        $registData['user_id'] = $conversation->userId;
        $registData['data']['username'] = $conversation->username;
        $registData['data']['type'] = $conversation->type;
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

        $reqData->text = ( UserText::getRegistSuccessText($conversation) )->get();
        $response = Request::sendMessage($reqData->build());
        AdminController::whenRegistUser($registration['id']);
        
        return $response;
    }

    public static function whenRegistApproved($telegramUser)
    {
        if(!$telegramUser) {
            return Request::emptyResponse();
        }

        $conversation = new Conversation('regist', null, $telegramUser['chat_id']);
        if($conversation->isExists()) {
            $conversation->done();
        }

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $telegramUser['chat_id'];
        $reqData->text = UserText::getRegistApprovedText($telegramUser['created_at'])->get();

        return Request::sendMessage($reqData->build());
    }

    public static function whenRegistRejected($registData)
    {
        if(!$registData) {
            return Request::emptyResponse();
        }

        $conversation = new Conversation('regist', null, $registData['chat_id']);
        if($conversation->isExists()) {
            $conversation->done();
        }

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $registData['chat_id'];

        $reqData->text = TelegramText::create()
            ->addBold('Pendaftaran Opnimus ditolak.')->newLine()
            ->addItalic($registData['updated_at'])->newLine(2)
            ->addText('Mohon maaf, permintaan anda tidak mendapat persetujuan oleh Admin. ')
            ->addText('Anda dapat berkoordinasi dengan Admin lokal anda untuk mendapatkan informasi terkait.')->newLine()
            ->addText('Terima kasih.')
            ->get();

        return Request::sendMessage($reqData->build());
    }
}