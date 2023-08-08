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
use App\Model\TelegramAdmin;
use App\Model\Registration;
use App\Model\Regional;
use App\Model\Witel;
use App\BuiltMessageText\AdminText;


class AdminController extends BotController
{
    protected static $callbacks = [
        'admin.user_approval' => 'onUserApproval',
    ];

    public static function getUserRegistConversation()
    {
        if($command = AdminController::$command) {
            if($command->getMessage()) {
                $chatId = AdminController::$command->getMessage()->getChat()->getId();
                $userId = AdminController::$command->getMessage()->getFrom()->getId();
                return new Conversation('user_regist', $userId, $chatId);
            } elseif($command->getCallbackQuery()) {
                $chatId = AdminController::$command->getCallbackQuery()->getMessage()->getChat()->getId();
                $userId = AdminController::$command->getCallbackQuery()->getFrom()->getId();
                return new Conversation('user_regist', $userId, $chatId);
            }
        }

        return null;
    }

    public static function whenRegistUser($registId)
    {
        $registData = Registration::find($registId);
        $admins = TelegramAdmin::getByUserArea($registData);
        if(count($admins) < 1) return;

        $reqData = new RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->text = AdminText::getUserApprovalText($registData)->get();
        $reqData->replyMarkup = new InlineKeyboard([
            ['text' => '👍 Izinkan', 'callback_data' => 'admin.user_approval.approve'],
            ['text' => '❌ Tolak', 'callback_data' => 'admin.user_approval.reject']
        ]);

        foreach($admins as $admin) {
            $reqData->chatId = $admin['chat_id'];
            Request::sendMessage($reqData->build());

            $conversation = Conversation::getOrCreate('user_regist', $admin['chat_id'], $admin['chat_id']);
            $conversation->registId = $registData['id'];
            $conversation->commit();
        }
    }

    public static function onUserApproval($data, $callbackQuery)
    {
        $conversation = AdminController::getUserRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $userId = AdminController::$command->getCallbackQuery()->getFrom()->getId();
        $conversation->setUserId($userId);

        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $user = $callbackQuery->getFrom();

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();

        $registData = Registration::find($conversation->registId);
        $updateText = AdminText::getUserApprovalText($registData);
        // return Request::sendMessage([ 'chat_id' => 1931357638, 'text' => json_encode($registData) ]);

        if(!$registData) {
            $reqData->text = $updateText->newLine(2)
                ->addText('Permintaan registrasi tidak dapat ditemukan. Hal ini dapat dikarenakan permintaan telah dihapus.')
                ->get();
            return Request::editMessageText($reqData->build());
        }

        $registStatus = Registration::getStatus($registData['id']);
        if($registStatus['status'] != 'unprocessed') {
            $statusText = $registStatus['status'] == 'approved' ? 'disetujui' : 'ditolak';

            if($registStatus['updated_by']) {
                $updateText->newLine(2)
                    ->addText("Permintaan registrasi telah $statusText oleh ")
                    ->addMention($registStatus['updated_by']['chat_id']);

                if($registStatus['updated_by']['witel_name']) {
                    $updateText->addText(' -'.$registStatus['updated_by']['witel_name'].'.');
                } elseif($registStatus['updated_by']['regional_name']) {
                    $updateText->addText(' -'.$registStatus['updated_by']['regional_name'].'.');
                } else {
                    $updateText->addText(' -NASIONAL.');
                }
            } else {
                $updateText->newLine(2)->addText("Permintaan registrasi telah $statusText.");
            }


            $reqData->text = $updateText->get();
            return Request::editMessageText($reqData->build());
        }

        $answerText = $data == 'approve' ? 'Izinkan' : 'Tolak';
        if(!$message->getChat()->isPrivateChat()) {
            $updateText = $updateText->newLine(2)->addMention($user->getId())->startBold()->addText(' > ')->endBold()->addText($answerText);
        } else {
            $updateText = $updateText->newLine(2)->startBold()->addText('=> ')->endBold()->addText($answerText);
        }

        $reqData->text = $updateText->get();
        $response = Request::editMessageText($reqData->build());
        $conversation->nextStep();
        $conversation->commit();
        $conversation->done();

        if($response->isOk() && $data == 'approve') {

            Registration::update($registData['id'], [
                'status' => 'approved'
            ]);
            
            $dataUser = [];
            $dataUser['chat_id'] = $registData['chat_id'];
            $dataUser['username'] = $registData['username'];
            if($registData['first_name']) $dataUser['first_name'] = $registData['first_name'];
            if($registData['last_name']) $dataUser['last_name'] = $registData['last_name'];
            $dataUser['type'] = $registData['type'];
            $dataUser['regist_id'] = $registData['id'];
            $dataUser['is_organik'] = $registData['is_organik'];
            if($registData['nik']) $dataUser['nik'] = $registData['nik'];
            $dataUser['level'] = $registData['level'];
            $dataUser['alert_status'] = 1;
            if($registData['regional_id']) $dataUser['regional_id'] = $registData['regional_id'];
            if($registData['witel_id']) $dataUser['witel_id'] = $registData['witel_id'];

            $telegramUser = TelegramUser::create($dataUser);

            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            $reqData1->text = 'Akses telah diizinkan.';
            $response = Request::sendMessage($reqData1->build());

            if($telegramUser['id']) {
                UserController::whenRegistApproved($telegramUser['id']);
            }

        } elseif($response->isOk() && $data == 'reject') {

            Registration::update($registData['id'], [
                'status' => 'rejected'
            ]);

            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            $reqData1->text = 'Permohonan registrasi telah ditolak.';
            $response = Request::sendMessage($reqData1->build());

        }
        
        return $response;
    }

    // private static function saveRegistFromConversation()
    // {
    //     $conversation = UserController::getRegistConversation();
    //     if(!$conversation->isExists()) {
    //         return Request::emptyResponse();
    //     }

        // $data = [];
        // $data['chat_id'] = $conversation->chatId;
        // $data['username'] = $conversation->username;
        // $data['first_name'] = $conversation->firstName;
        // $data['last_name'] = $conversation->lastName;
        // $data['type'] = $conversation->type;
        // $data['regist_id'] = 0;
        // $data['is_organik'] = 0;
        // $data['alert_status'] = 1;
        // $data['level'] = $conversation->level;
        
        // if($conversation->level == 'regional' || $conversation->level == 'witel') {
        //     $data['regional_id'] = $conversation->regionalId;
        // }

        // if($conversation->level == 'witel') {
        //     $data['witel_id'] = $conversation->witelId;
        // }

        // TelegramUser::create($data);

    //     $reqData = New RequestData();
    //     $reqData->parseMode = 'markdown';
    //     $reqData->chatId = $conversation->chatId;
    //     $reqData->text = TelegramText::create()
    //         ->startBold()->addText('Pendaftaran Opnimus berhasil.')->endBold()->newLine()
    //         ->startItalic()->addText(date('Y-m-d H:i:s'))->endItalic()->newLine(2)
    //         ->addText('Proses pendaftaran anda telah mendapat persetujuan Admin. Dengan ini, lokasi-lokasi yang memiliki RTU Osase akan memberi informasi lengkap mengenai Network Element anda. Apabila ada alarm atau RTU yang down akan langsung dilaporkan ke grup ini.')->newLine()
    //         ->addText('Untuk mengecek alarm kritis saat ini, pilih /alarm')->newLine()
    //         ->addText('Untuk melihat statistik RTU beserta MD nya pilih /rtu')->newLine()
    //         ->addText('Untuk bantuan dan daftar menu pilih /help.')->newLine()
    //         ->addText('Terima kasih.')->newLine(2)
    //         ->addText('OPNIMUS, Stay Alert, Stay Safe ')->newLine(2)
    //         ->addText('#PeduliInfrastruktur #PeduliCME')
    //         ->get();

    //     $response = Request::sendMessage($reqData->build());
    //     $conversation->done();

    //     return $response;
    // }
}