<?php

use App\Model\TelegramAdmin;
use App\Model\Registration;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\AlertUsers;
use App\Controller\Bot\UserController;

$message = $callbackQuery->getMessage();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();

list($callbackAnswer, $registId) = explode(':', $callbackData);
$regist = Registration::find($registId);

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

if(!$regist) {
    
    $request = static::request('Registration/TextNotFound');
    $request->params->chatId = $chatId;
    $request->params->messageId = $messageId;
    return $request->sendUpdate();

}

$prevRequest = static::request('Registration/SelectAdminApproval');
$prevRequest->setRegistrationData($regist);
if(in_array($regist['data']['level'], [ 'regional', 'witel' ])) {
    $regional = Regional::find($regist['data']['regional_id']);
    $prevRequest->setRegional($regional);
}
if($regist['data']['level'] == 'witel') {
    $witel = Witel::find($regist['data']['witel_id']);
    $prevRequest->setWitel($witel);
}
$prevRequestText = $prevRequest->params->text;

if($regist['status'] != 'unprocessed') {

    $request = static::request('Registration/TextDoneReviewed');
    $request->params->chatId = $chatId;
    $request->setStatusText( $regist['status'] == 'approved' ? 'disetujui' : 'ditolak' );

    $admin = TelegramAdmin::find($regist['updated_by']);
    if($admin) {
        if($admin['level'] == 'regional') {
            $regional = Regional::find($admin['regional_id']);
            $admin['regional_name'] = $regional ? $regional['name'] : 'NULL';
        } elseif($admin['level'] == 'witel') {
            $witel = Witel::find($admin['witel_id']);
            $admin['witel_name'] = $witel ? $witel['witel_name'] : 'NULL';
        }
    }
    $request->setAdminData($admin);
    $request->params->text = $prevRequest->getText()->newLine(2)->addText($request->params->text)->get();
    return $request->send();

}

$admin = TelegramAdmin::findByChatId($chatId);
if($callbackAnswer != 'approve') {
    
    Registration::update($regist['id'], [ 'status' => 'rejected' ], $admin['id']);

    $request = static::request('TextDefault');
    $request->params->chatId = $chatId;
    $request->setText(fn($text) => $text->addText($prevRequestText)->newLine(2)->addText('Permohonan registrasi telah ditolak.'));
    $response = $request->send();

    if(isset($regist['data']['approval_messages']) && count($regist['data']['approval_messages']) > 0) {
        try {

            $apprMsgs = $regist['data']['approval_messages'];
    
            $request = static::request('Registration/TextDoneReviewed');
            $request->setStatusText('ditolak');

            if($admin['level'] == 'regional') {
                $regional = Regional::find($admin['regional_id']);
                $admin['regional_name'] = $regional ? $regional['name'] : 'NULL';
            } elseif($admin['level'] == 'witel') {
                $witel = Witel::find($admin['witel_id']);
                $admin['witel_name'] = $witel ? $witel['witel_name'] : 'NULL';
            }
            $request->setAdminData($admin);

            $request->params->text = $prevRequest->getText()->newLine(2)->addText($request->params->text)->get();
            $responses = [];
            foreach($apprMsgs as $apprMsg) {
                if($apprMsg['chat_id'] != $chatId) {
                    $request->params->chatId = $apprMsg['chat_id'];
                    $request->params->messageId = $apprMsg['message_id'];
                    $request->sendUpdate();
                }
            }

        } catch(\Throwable $rr) {
            static::logError( new \MuhammadSabri1306\MyBotLogger\Entities\ErrorLogger($err) );
        }
    }

    UserController::whenRegistRejected($regist['id']);
    return $response;
    
}

Registration::update($regist['id'], [ 'status' => 'approved' ], $admin['id']);

$registUser = $regist['data'];
$dataUser = [];

$dataUser['chat_id'] = $regist['chat_id'];
$dataUser['user_id'] = $regist['user_id'];
$dataUser['username'] = $registUser['username'];
$dataUser['type'] = $registUser['type'];
$dataUser['level'] = $registUser['level'];
$dataUser['regist_id'] = $regist['id'];

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

if(isset($registUser['message_thread_id'])) {
    $dataUser['message_thread_id'] = $registUser['message_thread_id'];
}

$telgUser = TelegramUser::create($dataUser);
if($registUser['type'] == 'private') {

    TelegramPersonalUser::create([
        'user_id' => $telgUser['id'],
        'nama' => $registUser['full_name'],
        'telp' => $registUser['telp'],
        'instansi' => $registUser['instansi'],
        'unit' => $registUser['unit'],
        'is_organik' => $registUser['is_organik'] ? 1 : 0,
        'nik' => $registUser['nik']
    ]);

}

$useAlert = false;
if($registUser['is_pic']) {
    $useAlert = true;
} elseif($registUser['type'] != 'private' && $registUser['level'] == 'witel') {
    $useAlert = AlertUsers::findPivot($registUser['level'], $registUser['witel_id']) ? false : true;
} elseif($registUser['type'] != 'private' && $registUser['level'] == 'regional') {
    $useAlert = AlertUsers::findPivot($registUser['level'], $registUser['regional_id']) ? false : true;
} elseif($registUser['type'] != 'private' && $registUser['level'] == 'nasional') {
    $useAlert = AlertUsers::findPivot($registUser['level']) ? false : true;
}

if($useAlert) {

    $dataAlert = [];
    $dataAlert['id'] = $telgUser['id'];
    $dataAlert['mode_id'] = 1;
    $dataAlert['cron_alert_status'] = 1;
    $dataAlert['user_alert_status'] = 1;
    
    if(!$registUser['is_pic']) {
        $dataAlert['is_pivot_group'] = 1;
        $dataAlert['pivot_level'] = $registUser['level'];
        if($registUser['level'] == 'regional') {
            $dataAlert['pivot_id'] = $registUser['regional_id'];
        } elseif($registUser['level'] == 'witel') {
            $dataAlert['pivot_id'] = $registUser['witel_id'];
        }
    }

    AlertUsers::create($dataAlert);

}

$request = static::request('TextDefault');
$request->params->chatId = $chatId;
$request->setText(fn($text) => $text->addText($prevRequestText)->newLine(2)->addText('Akses telah diizinkan.'));
$response = $request->send();

if(isset($regist['data']['approval_messages']) && count($regist['data']['approval_messages']) > 0) {
    try {

        $apprMsgs = $regist['data']['approval_messages'];

        $request = static::request('Registration/TextDoneReviewed');
        $request->setStatusText('disetujui');

        if($admin['level'] == 'regional') {
            $regional = Regional::find($admin['regional_id']);
            $admin['regional_name'] = $regional ? $regional['name'] : 'NULL';
        } elseif($admin['level'] == 'witel') {
            $witel = Witel::find($admin['witel_id']);
            $admin['witel_name'] = $witel ? $witel['witel_name'] : 'NULL';
        }
        $request->setAdminData($admin);

        $request->params->text = $prevRequest->getText()->newLine(2)->addText($request->params->text)->get();
        foreach($apprMsgs as $apprMsg) {
            if($apprMsg['chat_id'] != $chatId) {
                $request->params->chatId = $apprMsg['chat_id'];
                $request->params->messageId = $apprMsg['message_id'];
                $request->sendUpdate();
            }
        }

    } catch(\Throwable $rr) {
        static::logError( new \MuhammadSabri1306\MyBotLogger\Entities\ErrorLogger($err) );
    }
}

UserController::whenRegistApproved($regist['id']);
return $response;