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
use App\Model\AlertUsers;
use App\BuiltMessageText\AdminText;
use App\Core\CallbackData;


class AdminController extends BotController
{
    public static $callbacks = [
        'admin.user_approval' => 'onUserApproval',
        'admin.picaprv' => 'onPicApproval',
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
        return static::callModules('when-regist-pic', [ 'registId' => $registId ]);
    }

    public static function whenRequestAlertExclusion($registId)
    {
        $registration = Registration::find($registId);
        $admins = TelegramAdmin::getSuperAdmin();
        // $admins = [TelegramAdmin::findByChatId('1931357638')];
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

    public static function onUserApproval($callbackData, $callbackQuery)
    {
        return static::callModules('on-user-approval', [
            'callbackData' => $callbackData,
            'callbackQuery' => $callbackQuery,
        ]);
    }

    public static function onPicApproval($callbackData, $callbackQuery)
    {
        return static::callModules('on-pic-approval', [
            'callbackData' => $callbackData,
            'callbackQuery' => $callbackQuery,
        ]);
    }

    public static function onAlertExclusionApproval($callbackData, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $messageText = $message->getText(true);
        $chatId = $message->getChat()->getId();

        list($callbackAnswer, $registId) = explode(':', $callbackData);
        $registration = Registration::find($registId);
        static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

        if(!$registration) {

            $request = BotController::request('Registration/TextNotFound');
            $request->params->chatId = $chatId;
            $request->params->messageId = $messageId;
            return $request->sendUpdate();

        }

        $prevRequest = static::request('AlertStatus/SelectAdminExclusionApproval');
        $prevRequest->setRegistrationData($registration);
        if(in_array($registration['data']['request_group']['level'], [ 'regional', 'witel' ])) {
            $regional = Regional::find($registration['data']['request_group']['regional_id']);
            $prevRequest->setRegional($regional);
        }
        if($registration['data']['request_group']['level'] == 'witel') {
            $witel = Witel::find($registration['data']['request_group']['witel_id']);
            $prevRequest->setWitel($witel);
        }

        if($registration['status'] != 'unprocessed') {

            $registStatus = Registration::getStatus($registration['id']);
            $request = BotController::request('Registration/TextDoneReviewed');
            $request->setStatusText($registStatus['status'] == 'approved' ? 'disetujui' : 'ditolak');
            $request->setAdminData($registStatus['updated_by']);
            $request->params->chatId = $chatId;
            $request->params->messageId = $messageId;
            $request->params->text = $prevRequest->getText()->newLine(2)->addText($request->params->text)->get();
            return $request->sendUpdate();

        }

        $admin = TelegramAdmin::findByChatId($chatId);
        $prevRequestText = $prevRequest->getText()->get();

        if($callbackAnswer != 'approve') {

            Registration::update($registration['id'], [ 'status' => 'rejected' ], $admin['id']);
            $request = BotController::request('TextDefault');
            $request->setText(fn($text) => $text->addText($prevRequestText)->newLine(2)->addText('Permintaan')->addBold(' Penambahan Alerting ')->addText('ditolak.'));
            $request->params->chatId = $chatId;
            $response = $request->send();

            AlertController::whenRequestExclusionReviewed(false, $registration['id']);
            return $response;
            
        }

        Registration::update($registration['id'], [ 'status' => 'approved' ], $admin['id']);
        
        $telgUserId = $registration['data']['request_group']['id'];
        $alertUser = AlertUsers::find($telgUserId);
        if($alertUser) {

            $alertUser = AlertUsers::update($alertUser['alert_user_id'], [
                'user_alert_status' => 1
            ]);

        } else {
            
            $alertUser = AlertUsers::create([
                'id' => $telgUserId,
                'mode_id' => 1,
                'cron_alert_status' => 1,
                'user_alert_status' => 1,
                'is_pivot_group' => 0
            ]);

        }

        if(!$alertUser || $alertUser['user_alert_status'] != 1) {
            throw new \Exception('Data alert_users.user_alert_status is not updated, alert_user_id:'.$telgUserId);
        }

        $request = BotController::request('TextDefault');
        $request->setText(fn($text) => $text->addText($prevRequestText)->newLine(2)->addText('Pengajuan')->addBold(' Penambahan Alerting ')->addText('disetujui.'));
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