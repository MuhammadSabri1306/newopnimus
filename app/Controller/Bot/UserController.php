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
use App\Model\Regional;
use App\Model\Witel;

// function debugConv($conversation, $reqData) {
//     $reqData->text = $conversation->toJson();
//     Request::sendMessage($reqData->build());
// }

// function debug($reqData, ...) {
//     $reqData->text = $conversation->toJson();
//     Request::sendMessage($reqData->build());
// }

class UserController extends BotController
{
    protected static $callbacks = [
        'user.regist_approval' => 'onRegist',
        'user.regist_level' => 'onSelectLevel',
        'user.select_regional' => 'onSelectRegional',
        'user.select_witel' => 'onSelectWitel',
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
            ['text' => '👍 Setuju', 'callback_data' => 'user.regist_approval.agree'],
            ['text' => '❌ Tidak', 'callback_data' => 'user.regist_approval.disagree']
        ]);

        return Request::sendMessage($reqData3->build());        
    }

    public static function register()
    {
        $conversation = UserController::getRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $message = UserController::$command->getMessage();
        $reqData = New RequestData();

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->replyMarkup = Keyboard::remove(['selective' => true]);
        
        if($message->getChat()->isGroupChat() || $message->getChat()->isSuperGroup()) {
            $reqData->replyMarkup = Keyboard::forceReply(['selective' => true]);
        }

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
        return $response;
    }

    public static function onRegist($data, $callbackQuery)
    {
        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $user = $callbackQuery->getFrom();

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();

        if($data == 'disagree') {

            $updateText = TelegramText::create()
                ->addText('Sebelum mendaftar OPNIMUS apakah anda setuju dengan Ketentuan Penggunaan diatas?')
                ->newLine(2);

            if(!$message->getChat()->isPrivateChat()) {
                $updateText = $updateText->addMention($user->getId())->startBold()->addText(' > ')->endBold()->addText('Tidak');
            } else {
                $updateText = $updateText->startBold()->addText('=> ')->endBold()->addText('Tidak');
            }

            $reqData->text = $updateText->get();
            $response = Request::editMessageText($reqData->build());

            if($response->isOk()) {
                $reqData1 = $reqData->duplicate('parseMode', 'chatId');
                $reqData1->text = 'Proses registrasi dibatalkan. Terima kasih.';
                $response = Request::sendMessage($reqData1->build());
            }
            
            $conversation = UserController::getRegistConversation();
            if($conversation->isExists()) {
                $conversation->cancel();
            }

            return $response;

        }
        
        if($data == 'agree') {

            $updateText = TelegramText::create()
                ->addText('Sebelum mendaftar OPNIMUS apakah anda setuju dengan Ketentuan Penggunaan diatas?')
                ->newLine(2);
    
            if(!$message->getChat()->isPrivateChat()) {
                $updateText = $updateText->addMention($user->getId())->startBold()->addText(' > ')->endBold()->addText('Setuju');
            } else {
                $updateText = $updateText->startBold()->addText('=> ')->endBold()->addText('Setuju');
            }
    
            $reqData->text = $updateText->get();
            $response = Request::editMessageText($reqData->build());
    
            $conversation = UserController::getRegistConversation();
            if(!$conversation->isExists()) {

                $conversation->create();
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
    
            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            $reqData1->text = TelegramText::create()
                ->addText('Terima kasih. Silahkan memilih ')->startBold()->addText('Level Monitoring')->endBold()->addText('.')->newLine(2)
                ->startItalic()->addText('* Pilih Witel Apabila anda Petugas CME/Teknisi di Lokasi Tertentu')->endItalic()
                ->get();
                
            $reqData1->replyMarkup = new InlineKeyboard([
                ['text' => 'Nasional', 'callback_data' => 'user.regist_level.nasional'],
            ], [
                ['text' => 'Regional', 'callback_data' => 'user.regist_level.regional'],
                ['text' => 'Witel', 'callback_data' => 'user.regist_level.witel']
            ]);
    
            $response = Request::sendMessage($reqData1->build());
            return $response;

        }

        return Request::emptyResponse();
    }

    public static function onSelectLevel($data, $callbackQuery)
    {
        if(in_array($data, ['nasional', 'regional', 'witel'])) {
            $conversation = UserController::getRegistConversation();

            if(!$conversation->isExists()) {
                return Request::emptyResponse();
            }

            $conversation->level = $data;
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
            $updateText = $updateText->addMention($user->getId())->startBold()->addText(' > ')->endBold()->addText($data);
        } else {
            $updateText = $updateText->startBold()->addText('=> ')->endBold()->addText($data);
        }

        $reqData->text = $updateText->get();
        Request::editMessageText($reqData->build());

        if($conversation->level == 'nasional') {
            
            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            $reqData1->text = 'Nasional';
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
            // $reqData1->text = json_encode($inlineKeyboardData);
            return Request::sendMessage($reqData1->build());

        }
        
        $reqData1 = $reqData->duplicate('parseMode', 'chatId');
        $reqData1->text = json_encode(['data' => $data]);
        return Request::sendMessage($reqData1->build());
    }

    public static function onSelectRegional($data, $callbackQuery)
    {
        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $user = $callbackQuery->getUser();
        $regional = Regional::find($data);

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();

        $updateText = TelegramText::create($message->getText())->newLine(2);

        if(!$message->getChat()->isPrivateChat()) {
            $updateText = $updateText->addMention($user->getId())->startBold()->addText(' > ')->endBold()->addText($regional['name']);
        } else {
            $updateText = $updateText->startBold()->addText('=> ')->endBold()->addText($regional['name']);
        }

        $reqData->text = $updateText->get();
        Request::editMessageText($reqData->build());

        $conversation = UserController::getRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $conversation->regionalId = $data;
        $conversation->commit();

        if($conversation->level == 'regional') {
            
            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            if(!$message->getChat()->isPrivateChat()) {

                $answerText = TelegramText::create()
                    ->addText('Terima kasih, grup akan didaftarkan sesuai data berikut.')->newLine(2)
                    ->startCode()
                    ->addText("Nama Grup          : $conversation->username")->newLine()
                    ->addText('Regional           : '.$regional['name'])->newLine()
                    ->addText('RTU yang dimonitor : Seluruh RTU di regional ini')
                    ->endCode()->newLine(2);

            } else {

                $answerText = TelegramText::create()
                    ->addText('Terima kasih, anda akan didaftarkan sesuai data berikut.')->newLine(2)
                    ->startCode()
                    ->addText("Nama User          : $conversation->firstName $conversation->lastName")->newLine()
                    ->addText('Regional           : '.$regional['name'])->newLine()
                    ->addText('RTU yang dimonitor : Seluruh RTU di regional ini')
                    ->endCode()->newLine(2);

            }

            $answerText->addText('Silahkan menunggu Admin di '.$regional['name'].' untuk melakukan verifikasi terhadap permintaan anda, terima kasih.')->newLine(2)
                ->startItalic()->addText('OPNIMUS, Stay Alert, Stay Safe')->endItalic();
            $reqData1->text = $answerText->get();

            $conversation->nextStep();
            $conversation->commit();
            Request::sendMessage($reqData1->build());

            return UserController::saveFromConversation();
        
        }

        if($conversation->level == 'witel') {

            $reqData1 = $reqData->duplicate('parseMode', 'chatId');
            $reqData1->text = 'Pilih Witel yang akan dimonitor.';
            Request::sendMessage($reqData1->build());
            
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

        $updateText = TelegramText::create($message->getText())->newLine(2);

        if(!$message->getChat()->isPrivateChat()) {
            $updateText = $updateText->addMention($user->getId())->startBold()->addText(' > ')->endBold()->addText($witel['witel_name']);
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
            if(!$message->getChat()->isPrivateChat()) {

                $answerText = TelegramText::create()
                    ->addText('Terima kasih, grup akan didaftarkan sesuai data berikut.')->newLine(2)
                    ->startCode()
                    ->addText("Nama Grup          : $conversation->username")->newLine()
                    ->addText('Regional           : '.$regional['name'])->newLine()
                    ->addText('Witel              : '.$witel['witel_name'])->newLine()
                    ->addText('RTU yang dimonitor : Seluruh RTU di witel ini')
                    ->endCode()->newLine(2);

            } else {

                $answerText = TelegramText::create()
                    ->addText('Terima kasih, anda akan didaftarkan sesuai data berikut.')->newLine(2)
                    ->startCode()
                    ->addText("Nama User          : $conversation->firstName $conversation->lastName")->newLine()
                    ->addText('Regional           : '.$regional['name'])->newLine()
                    ->addText('Witel              : '.$witel['witel_name'])->newLine()
                    ->addText('RTU yang dimonitor : Seluruh RTU di witel ini')
                    ->endCode()->newLine(2);

            }

            $answerText->addText('Silahkan menunggu Admin di '.$regional['name'].' untuk melakukan verifikasi terhadap permintaan anda, terima kasih.')->newLine(2)
                ->startItalic()->addText('OPNIMUS, Stay Alert, Stay Safe')->endItalic();
            $reqData1->text = $answerText->get();

            $conversation->nextStep();
            Request::sendMessage($reqData1->build());
            return UserController::saveFromConversation();
        
        }

        return Request::emptyResponse();
    }

    private static function saveFromConversation()
    {
        $conversation = UserController::getRegistConversation();
        if(!$conversation->isExists()) {
            return Request::emptyResponse();
        }

        $data = [];
        $data['chat_id'] = $conversation->chatId;
        $data['username'] = $conversation->username;
        $data['first_name'] = $conversation->firstName;
        $data['last_name'] = $conversation->lastName;
        $data['type'] = $conversation->type;
        $data['regist_id'] = 0;
        $data['is_organik'] = 0;
        $data['alert_status'] = 1;
        
        if($conversation->level == 'regional' || $conversation->level == 'witel') {
            $data['regional_id'] = $conversation->regionalId;
        }

        if($conversation->level == 'witel') {
            $data['witel_id'] = $conversation->witelId;
        }

        TelegramUser::create($data);

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $conversation->chatId;
        $reqData->text = TelegramText::create()
            ->startBold()->addText('Pendaftaran Opnimus berhasil.')->endBold()->newLine()
            ->startItalic()->addText(date('Y-m-d H:i:s'))->endItalic()->newLine(2)
            ->addText('Proses pendaftaran anda telah mendapat persetujuan Admin. Dengan ini, lokasi-lokasi yang memiliki RTU Osase akan memberi informasi lengkap mengenai Network Element anda. Apabila ada alarm atau RTU yang down akan langsung dilaporkan ke grup ini.')->newLine()
            ->addText('Untuk mengecek alarm kritis saat ini, pilih /alarm')->newLine()
            ->addText('Untuk melihat statistik RTU beserta MD nya pilih /rtu')->newLine()
            ->addText('Untuk bantuan dan daftar menu pilih /help.')->newLine()
            ->addText('Terima kasih.')->newLine(2)
            ->addText('OPNIMUS, Stay Alert, Stay Safe ')->newLine(2)
            ->addText('#PeduliInfrastruktur #PeduliCME')
            ->get();

        $response = Request::sendMessage($reqData->build());
        $conversation->done();

        return $response;
    }
}