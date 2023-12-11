<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;

use App\Core\RequestData;
use App\Core\TelegramText;
use App\Core\Conversation;
use App\Controller\BotController;
use App\Controller\Bot\UserController;
use App\Controller\Bot\PicController;
use App\Controller\Bot\AlertController;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\TelegramAdmin;
use App\Model\Registration;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\RtuLocation;
use App\Model\PicLocation;
use App\BuiltMessageText\AdminText;
use App\Core\CallbackData;


class AdminController extends BotController
{
    public static $callbacks = [
        'admin.user_approval' => 'onUserApproval',
        'admin.pic_approval' => 'onPicApproval',
        'admin.alert_exclusion' => 'onAlertExclusionApproval',
        'admin.adminaprv' => 'onAdminApproval',
        'admin.start' => 'onRegistration',
        'admin.level' => 'onSelectLevel',
        'admin.reg' => 'onSelectRegional',
        'admin.wit' => 'onSelectWitel',
        'admin.organik' => 'onSelectOrganikStatus',
    ];

    public static function getRegistConversation()
    {
        if($command = AdminController::$command) {
            if($command->getMessage()) {
                $chatId = AdminController::$command->getMessage()->getChat()->getId();
                $userId = AdminController::$command->getMessage()->getFrom()->getId();
                return new Conversation('regist_admin', $userId, $chatId);
            } elseif($command->getCallbackQuery()) {
                $chatId = AdminController::$command->getCallbackQuery()->getMessage()->getChat()->getId();
                $userId = AdminController::$command->getCallbackQuery()->getFrom()->getId();
                return new Conversation('regist_admin', $userId, $chatId);
            }
        }

        return null;
    }

    public static function whenRegistUser($registId)
    {
        $registData = Registration::find($registId);
        $admins = TelegramAdmin::getByUserArea($registData['data']);
        if(!$registData || count($admins) < 1) return;

        $request = BotController::request('Registration/SelectAdminApproval');
        $request->setRegistrationData($registData);
        
        if(in_array($registData['data']['level'], [ 'regional', 'witel' ])) {
            $regional = Regional::find($registData['data']['regional_id']);
            $request->setRegional($regional);
        }

        if($registData['data']['level'] == 'witel') {
            $witel = Witel::find($registData['data']['witel_id']);
            $request->setWitel($witel);
        }

        $request->buildText();
        $request->setInKeyboard(function($inlineKeyboardData) use ($registId) {
            $inlineKeyboardData['approve']['callback_data'] = 'admin.user_approval.approve:'.$registId;
            $inlineKeyboardData['reject']['callback_data'] = 'admin.user_approval.reject:'.$registId;
            return $inlineKeyboardData;
        });

        foreach($admins as $admin) {
            $request->params->chatId = $admin['chat_id'];
            $request->send();
        }
    }

    public static function whenRegistPic($registId)
    {
        $registData = Registration::find($registId);
        if(!$registData) return null;

        $apprData = AdminController::buildPicApprvData($registData);
        $admins = TelegramAdmin::getByUserArea($apprData, 'request_level');
        if(count($admins) < 1) return;

        $request = BotController::request('Registration/SelectAdminPicApproval');
        $request->setRegistrationData($apprData);

        $regional = Regional::find($apprData['regional_id']);
        $request->setRegional($regional);

        $witel = Witel::find($apprData['witel_id']);
        $request->setWitel($witel);

        $locations = RtuLocation::getByIds($apprData['locations']);
        $request->setLocations($locations);

        $request->buildText();
        $request->setInKeyboard(function($inlineKeyboardData) use ($registId) {
            $inlineKeyboardData['approve']['callback_data'] = 'admin.pic_approval.approve:'.$registId;
            $inlineKeyboardData['reject']['callback_data'] = 'admin.pic_approval.reject:'.$registId;
            return $inlineKeyboardData;
        });

        foreach($admins as $admin) {
            $request->params->chatId = $admin['chat_id'];
            $request->send();
        }
    }

    public static function whenRequestAlertExclusion($registId)
    {
        $registration = Registration::find($registId);
        // $admins = TelegramAdmin::getSuperAdmin();
        $admins = [TelegramAdmin::findByChatId('1931357638')];
        if(!$registration || count($admins) < 1) {
            return;
        }

        $request = BotController::request('AlertStatus/SelectAdminExclusionApproval');
        $request->setRegistrationData($registration);

        if(in_array($registration['data']['request_group']['level'], [ 'regional', 'witel' ])) {
            $regional = Regional::find($registration['data']['request_group']['regional_id']);
            $request->setRegional($regional);
        }

        if($registration['data']['request_group']['level'] == 'witel') {
            $witel = Witel::find($registration['data']['request_group']['witel_id']);
            $request->setWitel($witel);
        }

        $request->setInKeyboard(function($inlineKeyboardData) use ($registId) {
            $inlineKeyboardData['approve']['callback_data'] = 'admin.alert_exclusion.approve:'.$registId;
            $inlineKeyboardData['reject']['callback_data'] = 'admin.alert_exclusion.reject:'.$registId;
            return $inlineKeyboardData;
        });

        foreach($admins as $admin) {
            $request->params->chatId = $admin['chat_id'];
            $request->send();
        }
    }

    public static function whenRegistAdmin($registration)
    {
        $admins = TelegramAdmin::getByUserArea($registration['data']);
        if(count($admins) < 1) return;

        $request = BotController::request('RegistrationAdmin/SelectAdminApproval');
        $request->setRegistrationData($registration);
        
        if(in_array($registration['data']['level'], [ 'regional', 'witel' ])) {
            $regional = Regional::find($registration['data']['regional_id']);
            $request->setRegional($regional);
        }

        if($registration['data']['level'] == 'witel') {
            $witel = Witel::find($registration['data']['witel_id']);
            $request->setWitel($witel);
        }

        $callbackData = new CallbackData('admin.adminaprv');
        $registId = $registration['id'];
        $request->setInKeyboard(function($inlineKeyboardData) use ($callbackData, $registId) {
            $inlineKeyboardData['approve']['callback_data'] = $callbackData->createEncodedData([ 'r'=> $registId, 'a' => 1 ]);
            $inlineKeyboardData['reject']['callback_data'] = $callbackData->createEncodedData([ 'r'=> $registId, 'a' => 0 ]);
            return $inlineKeyboardData;
        });

        foreach($admins as $admin) {
            $request->params->chatId = $admin['chat_id'];
            $request->send();
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

        if($registUser['is_pic']) {
            $dataUser['alert_status'] = 1;
        } elseif($registUser['type'] == 'private') {
            $dataUser['alert_status'] = 0;
        } elseif($registUser['level'] == 'witel') {
            $group = TelegramUser::findAlertWitelGroup($registUser['witel_id']);
            $dataUser['alert_status'] = $group ? 0 : 1;
        } elseif($registUser['level'] == 'regional') {
            $group = TelegramUser::findAlertRegionalGroup($registUser['regional_id']);
            $dataUser['alert_status'] = $group ? 0 : 1;
        } elseif($registUser['level'] == 'nasional') {
            $group = TelegramUser::findAlertNasionalGroup();
            $dataUser['alert_status'] = $group ? 0 : 1;
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
        $registration['data']['is_pic'] = 1;

        if(!$registData['has_regist']) {
            $telegramUser = AdminController::saveRegisteringUser($registration);
        } else {
            $telegramUser = TelegramUser::find($registData['telegram_user_id']);
            if($telegramUser) {
                TelegramUser::update($telegramUser['id'], [
                    'is_pic' => 1,
                    'pic_regist_id' => $registration['id']
                ]);
            }
        }

        if(!$telegramUser) return null;

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

    public static function onAlertExclusionApproval($callbackData, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $messageText = $message->getText(true);
        $chatId = $message->getChat()->getId();

        list($callbackAnswer, $registId) = explode(':', $callbackData);
        $registration = Registration::find($registId);

        if(!$registration) {

            $request = BotController::request('Registration/TextNotFound');
            $request->params->chatId = $chatId;
            $request->params->messageId = $messageId;
            return $request->sendUpdate();

        }

        if($registration['status'] != 'unprocessed') {

            $registStatus = Registration::getStatus($registration['id']);
            $request = BotController::request('Registration/TextDoneReviewed');
            $request->setStatusText($registStatus['status'] == 'approved' ? 'disetujui' : 'ditolak');
            $request->setAdminData($registStatus['updated_by']);
            $request->params->chatId = $chatId;
            $request->params->messageId = $messageId;
            return $request->sendUpdate();

        }

        $request = BotController::request('TextAnswerSelect', [
            $messageText,
            $callbackAnswer == 'approve' ? 'Izinkan' : 'Tolak'
        ]);
        $request->params->chatId = $chatId;
        $request->params->messageId = $messageId;
        $response = $request->send();
        if(!$response->isOk()) {
            return $response;
        }

        $admin = TelegramAdmin::findByChatId($chatId);

        if($callbackAnswer != 'approve') {

            Registration::update($registration['id'], [ 'status' => 'rejected' ], $admin['id']);
            $request = BotController::request('TextDefault');
            $request->setText(fn($text) => $text->addText('Permintaan')->addBold(' Penambahan Alerting ')->addText('ditolak.'));
            $request->params->chatId = $chatId;
            $response = $request->send();

            AlertController::whenRequestExclusionReviewed(false, $registration['id']);
            return $response;
            
        }

        Registration::update($registration['id'], [ 'status' => 'approved' ], $admin['id']);
        
        $telgUserId = $registration['data']['request_group']['id'];
        TelegramUser::update($telgUserId, [ 'alert_status' => 1 ]);
        
        $telgUser = TelegramUser::find($telgUserId);
        if(!$telgUser || $telgUser['alert_status'] != 1) {
            throw new \Exception('Data telegram_user.alert_status is not updated, id:'.$telgUserId);
        }

        $request = BotController::request('TextDefault');
        $request->setText(fn($text) => $text->addText('Pengajuan')->addBold(' Penambahan Alerting ')->addText('disetujui.'));
        $request->params->chatId = $chatId;
        $response = $request->send();

        AlertController::whenRequestExclusionReviewed(true, $registration['id']);
        return $response;
    }

    public static function onAdminApproval($callbackValue, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $request = BotController::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();
        
        $registration = Registration::find($callbackValue['r']);
        if(!$registration) {

            $request = BotController::request('Registration/TextNotFound');
            $request->params->chatId = $chatId;
            $request->params->messageId = $messageId;
            return $request->sendUpdate();

        }

        if($registration['status'] != 'unprocessed') {

            $registStatus = Registration::getStatus($registration['id']);
            $request = BotController::request('Registration/TextDoneReviewed');
            $request->setStatusText($registStatus['status'] == 'approved' ? 'disetujui' : 'ditolak');
            $request->setAdminData($registStatus['updated_by']);
            $request->params->chatId = $chatId;
            $request->params->messageId = $messageId;
            return $request->sendUpdate();

        }

        $admin = TelegramAdmin::findByChatId($chatId);
        if($callbackValue['a'] == 0) {

            Registration::update($registration['id'], [ 'status' => 'rejected' ], $admin['id']);
            $registration = Registration::find($registration['id']);
            $reviewDate = $registration['updated_at'];

            $request = BotController::request('TextDefault');
            $request->setText(fn($text) => $text->addText('Pengajuan')->addBold(' Admin ')->addText('ditolak.'));
            $request->params->chatId = $chatId;
            $response = $request->send();

            $request = BotController::request('TextDefault');
            $request->params->chatId = $registration['chat_id'];
            $request->setText(function($text) use ($reviewDate) {
                return $text->addBold('Pengajuan status Admin ditolak.')->newLine()
                    ->addItalic($reviewDate)->newLine(2)
                    ->addText('Mohon maaf, permintaan anda tidak mendapat persetujuan. ')
                    ->addText('Anda dapat berkoordinasi dengan Admin untuk mendapatkan informasi terkait.')->newLine()
                    ->addText('Terima kasih.');
            });
            $request->send();

            return $response;

        }

        $registData = $registration['data'];
        $registData['regist_id'] = $registration['id'];
        $admin = TelegramAdmin::create($registData);
        if(!$admin) {
            throw new \Exception('Admin registration data not saved, regist id:'.$registration['id']);
        }

        Registration::update($registration['id'], [ 'status' => 'approved' ], $admin['id']);
        $registration = Registration::find($registration['id']);
        $reviewDate = $registration['updated_at'];

        $request = BotController::request('TextDefault');
        $request->setText(fn($text) => $text->addText('Pengajuan')->addBold(' Admin ')->addText('disetujui.'));
        $request->params->chatId = $chatId;
        $response = $request->send();

        $request = BotController::request('TextDefault');
        $request->params->chatId = $registration['chat_id'];
        $request->setText(function($text) use ($reviewDate) {
            return $text->addBold('Pengajuan status Admin disetujui.')->newLine()
                ->addItalic($reviewDate)->newLine(2)
                ->addText('Permintaan status anda sebagai Admin telah mendapat persetujuan, terima kasih.');
        });
        $request->send();

        return $response;
    }

    public static function registration()
    {
        $message = AdminController::$command->getMessage();
        if($message->getChat()->getType() != 'private') {
            return Request::emptyResponse();
        }

        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();
        if(TelegramAdmin::findByChatId($chatId)) {

            $request = BotController::request('TextDefault');
            $request->params->chatId = $chatId;
            $request->setText(function($text) {
                return $text->addText('Anda telah terdaftar sebagai admin.');
            });
            return $request->send();

        }

        if(Registration::findUnprocessedByChatId($chatId)) {

            $request = BotController::request('TextDefault');
            $request->params->chatId = $chatId;
            $request->setText(fn($text) => $text->addText('Pengajuan anda telah dikirim ke Admin untuk ditinjau, terima kasih.'));
            return $request->send();

        }
        
        $conversation = AdminController::getRegistConversation();
        if(!$conversation->isExists() || $conversation->getStep() < 3) {

            if($conversation->isExists()) {
                $conversation->setStep(0);
                $conversation->commit();
            }

            $request = BotController::request('RegistrationAdmin/SelectRegistContinue');
            $request->params->chatId = $chatId;

            $callbackData = new CallbackData('admin.start');
            $request->setInKeyboard(function($inlineKeyboardData) use ($callbackData) {
                $inlineKeyboardData['continue']['callback_data'] = $callbackData->createEncodedData('continue');
                $inlineKeyboardData['cancel']['callback_data'] = $callbackData->createEncodedData('cancel');
                return $inlineKeyboardData;
            });

            return $request->send();

        }

        if($conversation->getStep() >= 3) {

            $messageText = trim($message->getText(true));
            if(empty($messageText)) {
                $request = BotController::request('TextDefault');
                $request->params->chatId = $chatId;
                $request->setText(fn($text) => $text->addText('Silahkan ketikkan NIK anda.'));
                return $request->send();
            }

            $conversation->nik = $messageText;
            $conversation->nextStep();
            $conversation->commit();
            $messageText = '';

        }

        $conversation->done();
        $registAdmin = [
            'request_type' => 'admin',
            'chat_id' => $conversation->chatId,
            'user_id' => $conversation->chatId,
            'data' => []
        ];

        $registAdmin['data']['chat_id'] = $conversation->chatId;
        $registAdmin['data']['username'] = $conversation->username;
        $registAdmin['data']['first_name'] = $conversation->firstName;
        $registAdmin['data']['last_name'] = $conversation->lastName;
        $registAdmin['data']['is_organik'] = $conversation->isOrganik;
        $registAdmin['data']['nik'] = $conversation->nik;
        $registAdmin['data']['level'] = $conversation->level;

        if($conversation->level == 'regional' || $conversation->level == 'witel') {
            $registAdmin['data']['regional_id'] = $conversation->regionalId;
        }

        if($conversation->level == 'witel') {
            $registAdmin['data']['witel_id'] = $conversation->witelId;
        }

        $registration = Registration::create($registAdmin);
        if(!$registration) {
            throw new \Exception('Admin registration data not saved, conversation id:'.$conversation->getId());
        }

        $request = BotController::request('TextDefault');
        $request->params->chatId = $chatId;
        $request->setText(fn($text) => $text->addText('Pengajuan anda telah dikirim ke Admin untuk ditinjau, terima kasih.'));
        $response = $request->send();

        AdminController::whenRegistAdmin($registration);
        return $response;
    }

    public static function onRegistration($callbackValue, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $user = $callbackQuery->getFrom();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $request = BotController::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();

        if($callbackValue != 'continue') {

            $request = BotController::request('TextDefault');
            $request->params->chatId = $chatId;
            $request->setText(function($text) {
                return $text->addText('Pengajuan Admin dibatalkan.');
            });
            return $request->send();

        }

        $conversation = AdminController::getRegistConversation();
        if(!$conversation->isExists()) {
            $conversation->create();
        }

        $conversation->chatId = $chatId;
        $conversation->username = $user->getUsername();
        $conversation->firstName = $user->getFirstName();
        $conversation->lastName = $user->getLastName();
        $conversation->nextStep();
        $conversation->commit();

        $request = BotController::request('RegistrationAdmin/SelectLevel');
        $request->params->chatId = $chatId;

        $callbackData = new CallbackData('admin.level');
        $request->setInKeyboard(function($inlineKeyboardData) use ($callbackData) {
            $inlineKeyboardData['nasional']['callback_data'] = $callbackData->createEncodedData('nasional');
            $inlineKeyboardData['regional']['callback_data'] = $callbackData->createEncodedData('regional');
            $inlineKeyboardData['witel']['callback_data'] = $callbackData->createEncodedData('witel');
            return $inlineKeyboardData;
        });

        return $request->send();
    }

    public static function onSelectLevel($callbackValue, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $request = BotController::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();

        $conversation = AdminController::getRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $conversation->level = $callbackValue;
        $conversation->nextStep();
        $conversation->commit();

        if($callbackValue == 'nasional') {

            $request = BotController::request('RegistrationAdmin/SelectIsOrganik');
            $request->params->chatId = $chatId;
            
            $callbackData = new CallbackData('admin.organik');
            $request->setInKeyboard(function($inlineKeyboardData) use ($callbackData) {
                $inlineKeyboardData['yes']['callback_data'] = $callbackData->createEncodedData(1);
                $inlineKeyboardData['no']['callback_data'] = $callbackData->createEncodedData(0);
                return $inlineKeyboardData;
            });

            return $request->send();

        }

        $regionals = Regional::getSnameOrdered();
        $request = BotController::request('Area/SelectRegional');
        $request->params->chatId = $chatId;
        $request->setData('regionals', $regionals);
        
        $callbackData = new CallbackData('admin.reg');
        $request->setInKeyboard(function($inlineKeyboardItem, $regional) use ($callbackData) {
            $inlineKeyboardItem['callback_data'] = $callbackData->createEncodedData($regional['id']);
            return $inlineKeyboardItem;
        });

        return $request->send();
    }

    public static function onSelectRegional($callbackValue, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $request = BotController::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();

        $conversation = AdminController::getRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $conversation->regionalId = $callbackValue;
        if($conversation->level != 'witel') {
            
            $conversation->nextStep();
            $conversation->commit();
            $request = BotController::request('RegistrationAdmin/SelectIsOrganik');
            $request->params->chatId = $chatId;
            
            $callbackData = new CallbackData('admin.organik');
            $request->setInKeyboard(function($inlineKeyboardData) use ($callbackData) {
                $inlineKeyboardData['yes']['callback_data'] = $callbackData->createEncodedData(1);
                $inlineKeyboardData['no']['callback_data'] = $callbackData->createEncodedData(0);
                return $inlineKeyboardData;
            });
            
            return $request->send();
            
        }
        
        $conversation->commit();
        $request = BotController::request('Area/SelectWitel');
        $request->params->chatId = $chatId;
        $request->setData('witels', Witel::getNameOrdered($conversation->regionalId));
        
        $callbackData = new CallbackData('admin.wit');
        $request->setInKeyboard(function($inlineKeyboardItem, $witel) use ($callbackData) {
            $inlineKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
            return $inlineKeyboardItem;
        });

        return $request->send();
    }

    public static function onSelectWitel($callbackValue, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $request = BotController::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();
        
        $conversation = AdminController::getRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $conversation->witelId = $callbackValue;
        $conversation->nextStep();
        $conversation->commit();

        $request = BotController::request('RegistrationAdmin/SelectIsOrganik');
        $request->params->chatId = $chatId;
        
        $callbackData = new CallbackData('admin.organik');
        $request->setInKeyboard(function($inlineKeyboardData) use ($callbackData) {
            $inlineKeyboardData['yes']['callback_data'] = $callbackData->createEncodedData(1);
            $inlineKeyboardData['no']['callback_data'] = $callbackData->createEncodedData(0);
            return $inlineKeyboardData;
        });

        return $request->send();
    }

    public static function onSelectOrganikStatus($callbackValue, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $request = BotController::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();
        
        $conversation = AdminController::getRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $test = [$conversation->witelId];
        $conversation->isOrganik = $callbackValue;
        $conversation->nextStep();
        $conversation->commit();

        $request = BotController::request('TextDefault');
        $request->params->chatId = $chatId;
        $request->setText(fn($text) => $text->addText('Silahkan ketikkan NIK anda.'));
        return $request->send();
    }
}