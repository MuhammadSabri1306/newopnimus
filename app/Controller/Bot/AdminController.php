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
use App\Model\PicLocation;
use App\BuiltMessageText\AdminText;


class AdminController extends BotController
{
    protected static $callbacks = [
        'admin.user_approval' => 'onUserApproval',
        'admin.pic_approval' => 'onPicApproval',
    ];

    public static function whenRegistUser($registId)
    {
        $registData = Registration::find($registId);
        $admins = TelegramAdmin::getByUserArea($registData['data']);
        if(!$registData || count($admins) < 1) return;

        $btnApprovalReq = AdminController::getBtnApproval(function($inlineKeyboardData) use ($registId) {
            $inlineKeyboardData['approve']['callback_data'] = 'admin.user_approval.approve:'.$registId;
            $inlineKeyboardData['reject']['callback_data'] = 'admin.user_approval.reject:'.$registId;
            return $inlineKeyboardData;
        });

        $btnApprovalReq->text = AdminText::getUserApprovalText($registData)->get();
        
        foreach($admins as $admin) {
            $btnApprovalReq->chatId = $admin['chat_id'];
            Request::sendMessage($btnApprovalReq->build());
        }
    }

    public static function buildPicApprvData($registData)
    {
        if(!is_array($registData)) {
            return $registData;
        }

        $apprData = $registData['data'];
        $apprData['request_level'] = 'pic';
        if($apprData['has_regist']) {

            $telgUser = TelegramUser::find($apprData['telegram_user_id']);
            $telgPersUser = TelegramPersonalUser::findByUserId($apprData['telegram_user_id']);

            $apprData['full_name'] = $telgPersUser['nama'];
            $apprData['telp'] = $telgPersUser['telp'];
            $apprData['level'] = $telgUser['level'];
            $apprData['nik'] = $telgPersUser['nik'];
            $apprData['is_organik'] = $telgPersUser['is_organik'];
            $apprData['instansi'] = $telgPersUser['instansi'];
            $apprData['unit'] = $telgPersUser['unit'];
            $apprData['regional_id'] = $telgUser['regional_id'];
            $apprData['witel_id'] = $telgUser['witel_id'];

        }

        return $apprData;
    }

    public static function whenRegistPic($registId)
    {
        $registData = Registration::find($registId);
        if(!$registData) return null;

        $apprData = AdminController::buildPicApprvData($registData);
        $admins = TelegramAdmin::getByUserArea($apprData, 'request_level');
        if(count($admins) < 1) return;

        $btnApprovalReq = AdminController::getBtnApproval(function($inlineKeyboardData) use ($registId) {
            $inlineKeyboardData['approve']['callback_data'] = 'admin.pic_approval.approve:'.$registId;
            $inlineKeyboardData['reject']['callback_data'] = 'admin.pic_approval.reject:'.$registId;
            return $inlineKeyboardData;
        });

        $btnApprovalReq->text = AdminText::getPicApprovalText($apprData)->get();

        foreach($admins as $admin) {
            $btnApprovalReq->chatId = $admin['chat_id'];
            Request::sendMessage($btnApprovalReq->build());
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
            $updateText = TelegramText::create("Permintaan registrasi telah $statusText oleh ");
            $adminUserId = $registStatus['updated_by']['chat_id'] ?? null;
            $adminFirstName = $registStatus['updated_by']['first_name'] ?? null;
            $adminLastName = $registStatus['updated_by']['last_name'] ?? null;
            $adminUsername = $registStatus['updated_by']['username'] ?? null;

            if($adminFirstName && $adminLastName) {
                $updateText->addText('Admin ')
                    ->addMentionByName($adminUserId, "$adminFirstName $adminLastName");
            } elseif($adminUsername) {
                $updateText->addText('Admin ')
                    ->addMentionByUsername($adminUserId, $adminUsername);
            } else {
                $updateText->addMentionByName($adminUserId, 'ADMIN');
            }

            if($registStatus['updated_by']['witel_name']) {
                $updateText->newLine()->addItalic('- '.$registStatus['updated_by']['witel_name'].'.');
            } elseif($registStatus['updated_by']['regional_name']) {
                $updateText->newLine()->addItalic('- '.$registStatus['updated_by']['regional_name'].'.');
            } else {
                $updateText->newLine()->addItalic('- Level NASIONAL.');
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

        if($registUser['type'] != 'private') {
            $dataUser['alert_status'] = 0;
        } else {
            $dataUser['alert_status'] = 1;
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
            $telegramUser = AdminController::saveRegisteringUser($registration);
        } else {
            $telegramUser = TelegramUser::find($registData['telegram_user_id']);
        }

        if(!$telegramUser) return null;

        TelegramUser::update($telegramUser['id'], [
            'is_pic' => 1,
            'pic_regist_id' => $registration['id']
        ]);

        $savedLocs = PicLocation::getByUser($telegramUser['id']);
        $savedLocIds = array_column($savedLocs, 'location_id');
        $requestLocIds = $registData['locations'];

        // create and update pic location
        foreach($requestLocIds as $locationId) {
            if(!in_array($locationId, $savedLocIds)) {
                PicLocation::create([
                    'regist_id' => $registration['id'],
                    'user_id' => $telegramUser['id'],
                    'location_id' => $locationId,
                ]);
            } else {
                PicLocation::update($locationId, [
                    'regist_id' => $registration['id']
                ]);
            }
        }

        // remove pic location
        foreach($savedLocs as $savedLocItem) {
            if(!in_array($savedLocItem['location_id'], $requestLocIds)) {
                PicLocation::delete($savedLocItem['id']);
            }
        }

        return TelegramUser::find($telegramUser['id']);
    }

    public static function onUserApproval($callbackData, $callbackQuery)
    {
        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $chatId = $message->getChat()->getId();
        $messageId = $message->getMessageId();

        list($callbackAnswer, $registId) = explode(':', $callbackData);
        $admin = TelegramAdmin::findByChatId($chatId);
        
        $registStatus = Registration::getStatus($registId);

        // $status, $registData
        extract(AdminController::getRegistData($registId));
        
        $reqData = AdminController::getUnavailableApproveText($registData);
        $reqData->chatId = $chatId;
        $reqData->messageId = $messageId;

        if($status == 'empty' || $status == 'done') {
            return Request::editMessageText($reqData->build());
        }

        $answerText = $callbackAnswer == 'approve' ? 'Izinkan' : 'Tolak';
        $questionText = AdminText::getUserApprovalText($registData)->get();
        $reqData->text = AdminController::getInKeyboardAnswerText($questionText, $answerText)->get();
        $response = Request::editMessageText($reqData->build());

        if(!$response->isOk()) {
            return $response;
        }

        if($callbackAnswer == 'approve') {

            Registration::update($registData['id'], [ 'status' => 'approved' ], $admin['id']);
            $telegramUser = AdminController::saveRegisteringUser($registData);

            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            $reqData1->text = 'Akses telah diizinkan.';
            
            $response = Request::sendMessage($reqData1->build());
            // UserController::whenRegistApproved($telegramUser);
            UserController::whenRegistApproved($registData['id']);
            return $response;
        }
        
        Registration::update($registData['id'], [ 'status' => 'rejected' ], $admin['id']);

        $reqData1 = $reqData->duplicate('parseMode', 'chatId');
        $reqData1->text = 'Permohonan registrasi telah ditolak.';
        Request::sendMessage($reqData1->build());

        return UserController::whenRegistRejected($registData);
    }

    public static function onPicApproval($callbackData, $callbackQuery)
    {
        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $chatId = $message->getChat()->getId();
        $messageId = $message->getMessageId();

        list($callbackAnswer, $registId) = explode(':', $callbackData);
        $admin = TelegramAdmin::findByChatId($chatId);

        // $status, $registData
        extract(AdminController::getRegistData($registId));
        
        $reqData = AdminController::getUnavailableApproveText($registData);
        $reqData->chatId = $chatId;
        $reqData->messageId = $messageId;

        if($status == 'empty' || $status == 'done') {
            return Request::editMessageText($reqData->build());
        }

        $apprData = AdminController::buildPicApprvData($registData);
        $answerText = $callbackAnswer == 'approve' ? 'Izinkan' : 'Tolak';
        $questionText = AdminText::getPicApprovalText($apprData)->get();
        $reqData->text = AdminController::getInKeyboardAnswerText($questionText, $answerText)->get();
        $response = Request::editMessageText($reqData->build());
        
        if(!$response->isOk()) {
            return $response;
        }

        if($callbackAnswer == 'approve') {

            Registration::update($registData['id'], [ 'status' => 'approved' ], $admin['id']);
            $telegramUser = AdminController::saveRegisteringPic($registData);
            if(!$telegramUser) return $response;

            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            $reqData1->text = 'Pengajuan PIC telah diizinkan.';

            $response = Request::sendMessage($reqData1->build());
            PicController::whenRegistApproved($telegramUser);
            return $response;

        }

        Registration::update($registData['id'], [ 'status' => 'rejected' ], $admin['id']);
        $registData = Registration::find($registData['id']);

        $reqData1 = $reqData->duplicate('parseMode', 'chatId');
        $reqData1->text = 'Pengajuan PIC telah ditolak.';
        Request::sendMessage($reqData1->build());
        
        return PicController::whenRegistRejected($registData);
    }
}