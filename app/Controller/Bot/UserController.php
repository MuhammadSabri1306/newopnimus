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
// use App\Core\SessionDebugger;
use App\Core\Conversation;
use App\Controller\BotController;
use App\Model\TelegramUser;

function debugConv($conversation, $reqData) {
    $reqData->text = $conversation->toJson();
    Request::sendMessage($reqData->build());
}

// function debug($reqData, ...) {
//     $reqData->text = $conversation->toJson();
//     Request::sendMessage($reqData->build());
// }

class UserController extends BotController
{
    public static $cdRegistStart = 'user.regist_start'; // callback data to start regist
    public static $cdRegistCancel = 'user.regist_cancel'; // callback data to cancel regist
    public static $cdRegistLevelNasional = 'user.regist.level_nasional';
    public static $cdRegistLevelRegional = 'user.regist.level_regional';
    public static $cdRegistLevelWitel = 'user.regist.level_witel';

    public static function getRegistConversation()
    {
        if($command = UserController::$command) {
            // $message = $command->getMessage() ?? $command->getCallbackQuery();
            // $callbackQuery = $command->getCallbackQuery();
            // $isMessage = $message ? true : false;
            
            // $chatId = $isMessage ? $message->getChat()->getId() : $callbackQuery->getMessage()->getChat()->getId();
            // $userId = $isMessage ? $message->getFrom()->getId() : $callbackQuery->getFrom()->getId();

            // return new Conversation('regist', $userId, $chatId);

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
        $reqData = New RequestData();

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        // $reqData->replyToMessageId = $message->getMessageId();

        $chatType = $message->getChat()->getType();
        $fullName = ($chatType=='group' || $chatType=='supergroup') ? 'Grup '.$message->getChat()->getTitle()
            : $message->getFrom()->getFirstName().' '.$message->getFrom()->getLastName();
            
        if(!TelegramUser::exists($reqData->chatId)) return null;
        
        $reqData->animation = 'https://giphy.com/gifs/transformers-optimus-prime-transformer-transformers-rise-of-the-beasts-Bf3Anv7HuOPHEPkiOx';
        $reqData->caption = "*$fullName sudah terdaftar dalam OPNIMUS:* \n\n silahkan pilih /help untuk petunjuk lebih lanjut.";
        return Request::sendAnimation($reqData->build());
    }

    public static function tou()
    {
        $message = UserController::$command->getMessage();
        $reqData = New RequestData();

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->replyMarkup = Keyboard::remove(['selective' => true]);
        
        if($message->getChat()->isGroupChat() || $message->getChat()->isSuperGroup()) {
            // Force reply is applied by default so it can work with privacy on
            $reqData->replyMarkup = Keyboard::forceReply(['selective' => true]);
        }

        $reqData1 = $reqData->duplicate('parseMode', 'chatId', 'replyMarkup');
        // $reqData1->replyToMessageId = $message->getMessageId();
        $reqData1->animation = 'https://giphy.com/gifs/transformers-optimus-prime-transformer-transformers-rise-of-the-beasts-Bf3Anv7HuOPHEPkiOx';
        $reqData1->caption = TelegramText::create()
            ->addText('Hello! Welcome Heroes! selamat datang di ')
            ->startBold()->addText('OPNIMUS')->endBold()
            ->newLine()->newLine()
            ->startCode()
            ->addText('OPNIMUS (Operational Network Infra Monitoring & Surveillance System)')
            ->addText(' adalah sebuah sistem monitoring kondisi kesehatan network')
            ->addText(' element secara realtime dan sistem early warning terhadap potensi gangguan')
            ->addText(' karena permasalahan fisik (catuan, suhu, arus listrik).')
            ->endCode()
            ->get();

        $response = Request::sendAnimation($reqData1->build());

        $reqData2 = $reqData->duplicate('parseMode', 'chatId', 'replyMarkup');
        // $reqData2->replyToMessageId = $response->getResult()->getMessageId();
        $reqData2->text = TelegramText::create()
            ->startBold()->addText('Term of Use | Ketentuan Penggunaan OPNIMUS')->endBold()->newLine(2)
            ->addTabspace()->addText('Pembaca diharuskan memahami beberapa persyaratan berikut yang ditetapkan oleh OPNIMUS. Jika anda tidak menyetujuinya, disarankan untuk tidak menggunakan tools ini.')->newLine(2)
            ->addTabspace()->startItalic()->addText('Konten')->endItalic()->newLine()
            ->addTabspace()->addText('1. Semua materi di dalam ')->startBold()->addText('OPNIMUS')->endBold()->addText(' tidak boleh disalin, diproduksi ulang, dipublikasikan, dan disebarluaskan baik berupa informasi, data, gambar, foto, logo dan lainnya secara utuh maupun sebagian dengan cara apapun dan melalui media apapun kecuali untuk keperluan Operasional.')->newLine()
            ->addTabspace()->addText('2. Aplikasi  ini berikut seluruh materinya tidak boleh dimanfaatkan untuk hal-hal yang bertujuan melanggar hukum atau norma agama dan masyarakat atau hak asasi manusia.')->newLine(2)
            ->addTabspace()->startItalic()->addText('Dalam Penggunaan OPNIMUS, anda setuju untuk:')->endItalic()->newLine()
            ->addTabspace()->addText('1. Memberikan informasi yang akurat, baru dan lengkap tentang diri Anda saat Mendaftar ')->startBold()->addText('OPNIMUS')->endBold()->addText('.')->newLine()
            ->addTabspace()->addText('2. ')->startBold()->addText('OPNIMUS')->endBold()->addText(' tidak bertanggungjawab atas dampak yang ditimbulkan atas penggunaan materi di dalam aplikasi.')->newLine()
            ->addTabspace()->addText('3. Anda tidak diperbolehkan menggunakan ')->startBold()->addText('OPNIMUS')->endBold()->addText(' dalam kondisi atau cara apapun yang dapat merusak, melumpuhkan, membebani dan/atau mengganggu server atau jaringan ')->startBold()->addText('OPNIMUS')->endBold()->addText('. Anda juga tidak diperbolehkan untuk mengakses layanan, akun pengguna, sistem komputer atau jaringan secara tidak sah, dengan cara perentasan (hacking), password mining, dan/atau cara lainnya.')->newLine(2)
            ->addTabspace()->addText('Dengan menggunakan atau mengakses (termasuk berbagai perubahan) ')->startBold()->addText('OPNIMUS')->endBold()->addText(' ini, anda berarti menyetujui syarat-syarat penggunaan ')->startBold()->addText('OPNIMUS')->endBold()->addText('.')
            ->get();

        $response = Request::sendMessage($reqData2->build());

        $reqData3 = $reqData->duplicate('parseMode', 'chatId');
        // $reqData3->replyToMessageId = $response->getResult()->getMessageId();
        $reqData3->text = 'Sebelum mendaftar OPNIMUS apakah anda setuju dengan Ketentuan Penggunaan diatas?';
        $reqData3->replyMarkup = new InlineKeyboard([
            ['text' => '👍 Setuju', 'callback_data' => UserController::$cdRegistStart],
            ['text' => '❌ Tidak', 'callback_data' => UserController::$cdRegistCancel]
        ]);

        return Request::sendMessage($reqData3->build());        
    }

    public static function onRegistStart()
    {
        $reqData = New RequestData();
        $message = UserController::$command->getCallbackQuery()->getMessage();
        $user = UserController::$command->getCallbackQuery()->getFrom();

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();

        $answerText = TelegramText::create()
            ->addText('Sebelum mendaftar OPNIMUS apakah anda setuju dengan Ketentuan Penggunaan diatas?')
            ->newLine(2);

        if(!$message->getChat()->isPrivateChat()) {
            $answerText = $answerText->addMention($user->getId())->startBold()->addText(' > ')->endBold();
        } else {
            $answerText = $answerText->startBold()->addText('=> ')->endBold()->addText('Setuju');
        }

        $reqData->text = $answerText->get();
        $response = Request::editMessageText($reqData->build());

        $conversation = UserController::getRegistConversation();
        if(!$conversation->isExists()) {
            $conversation->create();
        }

        $reqData1 = $reqData->duplicate('parseMode', 'chatId');
        $reqData1->text = TelegramText::create()
            ->addText('Terima kasih. Silahkan memilih ')->startBold()->addText('Level Monitoring')->endBold()->addText('.')->newLine(2)
            ->startItalic()->addText('* Pilih Witel Apabila anda Petugas CME/Teknisi di Lokasi Tertentu')->endItalic()
            ->get();
            
        $reqData1->replyMarkup = new InlineKeyboard([
            ['text' => 'Nasional', 'callback_data' => UserController::$cdRegistLevelNasional],
        ], [
            ['text' => 'Regional', 'callback_data' => UserController::$cdRegistLevelRegional],
            ['text' => 'Witel', 'callback_data' => UserController::$cdRegistLevelWitel]
        ]);

        $response = Request::sendMessage($reqData1->build());
        return $response;
    }

    public static function onRegistCancel()
    {
        $reqData = New RequestData();
        $message = UserController::$command->getCallbackQuery()->getMessage();
        $user = UserController::$command->getCallbackQuery()->getUser();

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();

        $answerText = TelegramText::create()
            ->addText('Sebelum mendaftar OPNIMUS apakah anda setuju dengan Ketentuan Penggunaan diatas?')
            ->newLine(2);

        if(!$message->getChat()->isPrivateChat()) {
            $answerText = $answerText->addMention($user->getId())->startBold()->addText(' > ')->endBold();
        } else {
            $answerText = $answerText->startBold()->addText('=> ')->endBold()->addText('Tidak');
        }

        $reqData->text = $answerText->get();
        $response = Request::editMessageText($reqData->build());

        if($response->isOk()) {
            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            // $reqData1->replyToMessageId = $response->getResult()->getMessageId();
            $reqData1->text = 'Proses registrasi dibatalkan. Terima kasih.';
            $response = Request::sendMessage($reqData1->build());
        }
        
        $conversation = UserController::getRegistConversation();
        if($conversation->isExists()) {
            $conversation->cancel();
        }

        return $response;
    }

    public static function register()
    {
        $message = UserController::$command->getMessage();
        $reqData = New RequestData();

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->replyMarkup = Keyboard::remove(['selective' => true]);
        
        if($message->getChat()->isGroupChat() || $message->getChat()->isSuperGroup()) {
            $reqData->replyMarkup = Keyboard::forceReply(['selective' => true]);
        }

        $conversation = UserController::getRegistConversation();
        $response = Request::emptyResponse();
        $text = trim($message->getText(true));

        switch ($conversation->getStep()) {
            case 0:
                if ($text === '') {
                    $reqData->text = TelegramText::create()
                        ->addText('Proses registrasi dimulai. Silahkan memilih ')->startBold()->addText('Level Monitoring')->endBold()->addText('.')->newLine(2)
                        ->startItalic()->addText('* Pilih Witel Apabila anda Petugas CME/Teknisi di Lokasi Tertentu')->endItalic()
                        ->get();
                        
                    $reqData->replyMarkup = new InlineKeyboard([
                        ['text' => 'Nasional', 'callback_data' => UserController::$cdRegistLevelNasional],
                    ], [
                        ['text' => 'Regional', 'callback_data' => UserController::$cdRegistLevelRegional],
                        ['text' => 'Witel', 'callback_data' => UserController::$cdRegistLevelWitel]
                    ]);
                    
                    return Request::sendMessage($reqData->build());
                    break;
                }

                $conversation->name = $text;
                $conversation->nextStep();
                $conversation->commit();

                $text = '';

            case 1:
                if ($text === '') {
                    $reqData->text = 'Type your surname:';
                    $response = Request::sendMessage($reqData->build());
                    break;
                }

                $conversation->surname = $text;
                $conversation->nextStep();
                $conversation->commit();
                $text = '';

            case 2:
                $reqText = TelegramText::create()->addText('Hasil:')->newLine();
                foreach ($conversation->getStateArray() as $key => $value) {
                    $reqText->newLine()->addText(ucfirst($key).': '.$value);
                }

                $reqData->text = $reqText->get();
                $conversation->done();

                $response = Request::sendMessage($reqData->build());
                break;
        }

        return $response;
    }
}