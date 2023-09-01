<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;

use App\Core\RequestData;
use App\Core\TelegramText;
use App\Core\Conversation;
use App\Controller\BotController;
use App\Controller\Bot\UserController;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\TelegramAdmin;
use App\Model\Registration;
use App\Model\Regional;
use App\Model\Witel;
use App\BuiltMessageText\AdminText;


class AdminController extends BotController
{
    protected static $callbacks = [
        'admin.user_approval' => 'onUserApproval',
        'admin.pic_approval' => 'onPicApproval',
    ];

    public static function getUserRegistConversation()
    {
        if($command = AdminController::$command) {
            if($command->getMessage()) {
                $chatId = AdminController::$command->getMessage()->getChat()->getId();
                $userId = AdminController::$command->getMessage()->getFrom()->getId();
                return new Conversation('regist', $userId, $chatId);
            } elseif($command->getCallbackQuery()) {
                $chatId = AdminController::$command->getCallbackQuery()->getMessage()->getChat()->getId();
                $userId = AdminController::$command->getCallbackQuery()->getFrom()->getId();
                return new Conversation('regist', $userId, $chatId);
            }
        }

        return null;
    }

    public static function getPicRegistConversation()
    {
        if($command = AdminController::$command) {
            if($command->getMessage()) {
                $chatId = AdminController::$command->getMessage()->getChat()->getId();
                $userId = AdminController::$command->getMessage()->getFrom()->getId();
                return new Conversation('regist_pic', $userId, $chatId);
            } elseif($command->getCallbackQuery()) {
                $chatId = AdminController::$command->getCallbackQuery()->getMessage()->getChat()->getId();
                $userId = AdminController::$command->getCallbackQuery()->getFrom()->getId();
                return new Conversation('regist_pic', $userId, $chatId);
            }
        }

        return null;
    }

    public static function whenRegistUser($registId)
    {
        $registData = Registration::find($registId);
        $admins = TelegramAdmin::getByUserArea($registData['data']);
        if(count($admins) < 1) return;

        $btnApprovalReq = AdminController::getBtnApproval(function($inlineKeyboardData) {
            $inlineKeyboardData['approve']['callback_data'] = 'admin.user_approval.approve';
            $inlineKeyboardData['reject']['callback_data'] = 'admin.user_approval.reject';
            return $inlineKeyboardData;
        });

        $btnApprovalReq->text = AdminText::getUserApprovalText($registData)->get();

        foreach($admins as $admin) {
            $btnApprovalReq->chatId = $admin['chat_id'];
            Request::sendMessage($btnApprovalReq->build());

            $conversation = Conversation::getOrCreate('regist', $admin['chat_id'], $admin['chat_id']);
            $conversation->registId = $registData['id'];
            $conversation->adminId = $admin['id'];
            $conversation->commit();
        }
    }

    public static function whenRegistPic($registId)
    {
        $registData = Registration::find($registId);
        $admins = TelegramAdmin::getByUserArea($registData['data']);
        if(count($admins) < 1) return;

        $btnApprovalReq = AdminController::getBtnApproval(function($inlineKeyboardData) {
            $inlineKeyboardData['approve']['callback_data'] = 'admin.pic_approval.approve';
            $inlineKeyboardData['reject']['callback_data'] = 'admin.pic_approval.reject';
        });

        $btnApprovalReq->text = AdminText::getPicApprovalText($registData)->get();

        foreach($admins as $admin) {
            $btnApprovalReq->chatId = $admin['chat_id'];
            Request::sendMessage($btnApprovalReq->build());

            $conversation = Conversation::getOrCreate('regist_pic', $admin['chat_id'], $admin['chat_id']);
            $conversation->registId = $registData['id'];
            $conversation->adminId = $admin['id'];
            $conversation->commit();
        }
    }

    public static function getBtnApproval(callable $callInKeyboard): RequestData
    {
        $reqData = new RequestData();
        $reqData->parseMode = 'markdown';

        $inlineKeyboardData = $callInKeyboard([
            'approve' => [ 'text' => '👍 Izinkan', 'callback_data' => null ],
            'reject' => [ 'text' => '❌ Tolak', 'callback_data' => null ]
        ]);

        $reqData->replyMarkup = new InlineKeyboard($inlineKeyboardData);
        return $reqData;
    }

    public static function getRegistData($registId)
    {
        $registData = Registration::find($registId);
        if(!$registData) {
            return [ 'status' => 'empty', 'registData' => $registData ];
        }
        
        $registStatus = Registration::getStatus($registId);
        if($registStatus['status'] != 'unprocessed') {
            return [ 'status' => 'done', 'registData' => $registData ];
        }
        
        return [ 'status' => 'exists', 'registData' => $registData ];
    }

    public static function getInKeyboardAnswerText($questionText, $answerText): TelegramText
    {
        return TelegramText::create($questionText)->newLine(2)
            ->addBold('=> ')->addText($answerText);
    }

    public static function getUnavailableApproveText($registData)
    {
        $reqData = new RequestData();
        $reqData->parseMode = 'markdown';
        
        if(!$registData) {
            $reqData->text = 'Permintaan registrasi tidak dapat ditemukan. Hal ini dapat dikarenakan permintaan telah dihapus.';
            return $reqData;
        }

        $updateText = AdminText::getUserApprovalText($registData);
        $registStatus = Registration::getStatus($registData['id']);
        
        $statusText = $registStatus['status'] == 'approved' ? 'disetujui' : 'ditolak';

        if(isset($registStatus['updated_by'])) {
            $updateText = TelegramText::create("Permintaan registrasi telah $statusText oleh ")
                ->addMention($registStatus['updated_by']['chat_id'], 'Admin lain');

            if($registStatus['updated_by']['witel_name']) {
                $updateText->addText(' -'.$registStatus['updated_by']['witel_name'].'.');
            } elseif($registStatus['updated_by']['regional_name']) {
                $updateText->addText(' -'.$registStatus['updated_by']['regional_name'].'.');
            } else {
                $updateText->addText(' -NASIONAL.');
            }

            $reqData->text = $updateText->get();
        } else {
            $reqData->text = "Permintaan registrasi telah $statusText.";
        }

        return $reqData;
    }

    public static function saveRegisteringUser($registData)
    {
        $dataUser = [];
        $dataPersonal = [];
        $registUser = $registData['data'];

        $dataUser['chat_id'] = $registData['chat_id'];
        $dataUser['user_id'] = $registData['user_id'];
        $dataUser['username'] = $registUser['username'];
        $dataUser['type'] = $registUser['type'];
        $dataUser['level'] = $registUser['level'];
        $dataUser['regist_id'] = $registData['id'];
        $dataUser['alert_status'] = 1;
        
        if($registUser['level'] == 'regional' || $registUser['level'] == 'witel') {
            $dataUser['regional_id'] = $registUser['regional_id'];
        }

        if($registUser['level'] == 'witel') {
            $dataUser['witel_id'] = $registUser['witel_id'];
        }

        if($registUser['type'] != 'private') {
            $dataUser['group_description'] = $registUser['group_description'];
        } else {
            $dataUser['first_name'] = $registUser['first_name'];
            $dataUser['last_name'] = $registUser['last_name'];
        }

        $telegramUser = TelegramUser::create($dataUser);
        if($registUser['type'] != 'private') {
            return $telegramUser;
        }
        
        $dataPersonal['user_id'] = $telegramUser['id'];
        $dataPersonal['nama'] = $registUser['full_name'];
        $dataPersonal['telp'] = $registUser['telp'];
        $dataPersonal['instansi'] = $registUser['instansi'];
        $dataPersonal['unit'] = $registUser['unit'];
        $dataPersonal['is_organik'] = $registUser['is_organik'] ? 1 : 0;
        $dataPersonal['nik'] = $registUser['nik'];

        $personalUser = TelegramPersonalUser::create($dataPersonal);
        return $personalUser ? $telegramUser : null;
    }

    public static function saveRegisteringPic($registration)
    {
        $dataUser = [];
        $dataPersonal = [];
        $registData = $registration['data'];
        
        if(!$registData['has_regist']) {
            $telegramUser = AdminController::saveRegisteringUser($registData);
        } else {
            $telegramUser = TelegramUser::find($registData['telegram_user_id']);
        }

        foreach($registData['locations'] as $locationId) {
            PicLocation::create([
                'regist_id' => $registration['id'],
                'user_id' => $telegramUser['id'],
                'location_id' => $locationId,
            ]);
        }

        return $telegramUser;
    }

    public static function onUserApproval($callbackData, $callbackQuery)
    {
        $conversation = AdminController::getUserRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $userId = AdminController::$command->getCallbackQuery()->getFrom()->getId();
        $conversation->setUserId($userId);

        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $chatId = $message->getChat()->getId();
        $messageId = $message->getMessageId();
        $user = $callbackQuery->getFrom();
        
        // $status, $registData
        extract(AdminController::getRegistData($conversation->registId));
        
        $reqData = AdminController::getUnavailableApproveText($registData);
        $reqData->chatId = $chatId;
        $reqData->messageId = $messageId;

        if($status == 'empty' || $status == 'done') {
            return Request::editMessageText($reqData->build());
        }

        $answerText = $callbackData == 'approve' ? 'Izinkan' : 'Tolak';
        $questionText = AdminText::getUserApprovalText($registData)->get();
        $reqData->text = AdminController::getInKeyboardAnswerText($questionText, $answerText)->get();
        $response = Request::editMessageText($reqData->build());

        $conversation->nextStep();
        $conversation->commit();
        $conversation->done();

        if(!$response->isOk()) {
            return $response;
        }

        if($callbackData == 'approve') {

            Registration::update($conversation->registId, [ 'status' => 'approved' ], $conversation->adminId);
            $telegramUser = AdminController::saveRegisteringUser($registData);

            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            $reqData1->text = 'Akses telah diizinkan.';
            Request::sendMessage($reqData1->build());

            return UserController::whenRegistApproved($telegramUser);

        }
        
        Registration::update($registData['id'], [ 'status' => 'rejected' ], $adminId);

        $reqData1 = $reqData->duplicate('parseMode', 'chatId');
        $reqData1->text = 'Permohonan registrasi telah ditolak.';
        Request::sendMessage($reqData1->build());

        return UserController::whenRegistRejected($registData);
    }

    public static function onPicApproval($callbackData, $callbackQuery)
    {
        $conversation = AdminController::getPicRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $userId = AdminController::$command->getCallbackQuery()->getFrom()->getId();
        $conversation->setUserId($userId);

        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $chatId = $message->getChat()->getId();
        $messageId = $message->getMessageId();
        $user = $callbackQuery->getFrom();
        
        // $status, $registData
        extract(AdminController::getRegistData($conversation->registId));
        
        $reqData = AdminController::getUnavailableApproveText($registData);
        $reqData->chatId = $chatId;
        $reqData->messageId = $messageId;

        if($status == 'empty' || $status == 'done') {
            return Request::editMessageText($reqData->build());
        }

        $answerText = $callbackData == 'approve' ? 'Izinkan' : 'Tolak';
        $questionText = AdminText::getPicApprovalText($registData)->get();
        $reqData->text = AdminController::getInKeyboardAnswerText($questionText, $answerText)->get();
        $response = Request::editMessageText($reqData->build());

        $conversation->nextStep();
        $conversation->commit();
        $conversation->done();

        if($response->isOk() && $callbackData == 'approve') {

            Registration::update($conversation->registId, [ 'status' => 'approved' ], $conversation->adminId);
            $telegramUser = AdminController::saveRegisteringPic($registData);
            if(!$telegramUser) return $response;

            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            $reqData1->text = 'Permintaan telah diizinkan.';
            Request::sendMessage($reqData1->build());
            return PicController::whenRegistApproved($telegramUser['id']);

        } elseif($response->isOk() && $callbackData == 'reject') {

            Registration::update($registData['id'], [ 'status' => 'rejected' ], $adminId);

            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            $reqData1->text = 'Permohonan registrasi telah ditolak.';
            $response = Request::sendMessage($reqData1->build());

        }
        
        return $response;
    }
}