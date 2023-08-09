<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;

use App\Core\RequestData;
use App\Core\TelegramText;
use App\Core\Conversation;
use App\Controller\BotController;
use App\Model\TelegramUser;

class PicController extends BotController
{
    protected static $callbacks = [
        'pic.set_start' => 'onSetStart',
    ];

    public static function getPicRegistConversation()
    {
        if($command = PicController::$command) {
            if($command->getMessage()) {
                $chatId = PicController::$command->getMessage()->getChat()->getId();
                $userId = PicController::$command->getMessage()->getFrom()->getId();
                return new Conversation('pic_regist', $userId, $chatId);
            } elseif($command->getCallbackQuery()) {
                $chatId = PicController::$command->getCallbackQuery()->getMessage()->getChat()->getId();
                $userId = PicController::$command->getCallbackQuery()->getFrom()->getId();
                return new Conversation('pic_regist', $userId, $chatId);
            }
        }

        return null;
    }

    public static function setLocations()
    {
        $message = PicController::$command->getMessage();
        $telegramUser = TelegramUser::findPicByChatId($message->getFrom()->getId());

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        
        if(!$message->getChat()->isPrivateChat()) {
            $replyText = TelegramText::create()
                ->addText('Mohon maaf, permintaan set PIC Lokasi tidak dapat dilakukan melalui grup. ')
                ->addText('Anda dapat melakukan private chat ')
                ->startBold()->addText('(japri)')->endBold()
                ->addText(' langsung ke bot @opnimusdev_bot dan mengetikkan perintah /setpic, terima kasih.')
                ->get();
            return PicController::$command->replyToChat($replyText);
        }

        if(!$telegramUser) {
            $telegramUser = TelegramUser::findByChatId($message->getFrom()->getId());
            if(!$telegramUser) {
                return Request::emptyResponse();
            }

            $telegramUser['locations'] = [];
        }

        $replyText = TelegramText::create()
            ->addText('Anda akan mendaftarkan diri anda menjadi PIC Lokasi. ')
            ->addText('Silahkan memanfaatkan fitur ini apabila anda merupakan pengawal perangkat Network Element Telkom Indonesia di lokasi tertentu.')->newLine(2);
        
        if(count($telegramUser['locations']) < 1) {
            $replyText->startItalic()->addText('Saat ini anda belum menjadi PIC di lokasi manapun.')->endItalic()->newLine(2);
        } else {
            foreach($telegramUser['locations'] as $loc) {
                $replyText->startItalic()
                    ->startBold()->addText('- '.$loc['location_name'].$loc['location_sname'])->endBold()
                    ->endItalic()->newLine();
            }
            $replyText->newLine();
        }

        $replyText
            ->addText('Dengan mendaftarkan diri anda sebagai PIC lokasi, anda akan mendapatkan:')->newLine()
            ->addText('📌 ')->startBold()->addText('Alert khusus di lokasi yang anda kawal via japrian OPNIMUS, dan')->endBold()->newLine()
            ->addText('📌 ')->startBold()->addText('Tagging nama anda di grup agar tidak ada alarm yang terlewat dan memudahkan respon.')->endBold()->newLine();
        
        $reqData->text = $replyText->get();
        $reqData->replyMarkup = new InlineKeyboard([
            ['text' => 'Lanjutkan', 'callback_data' => 'pic.set_start.continue'],
            ['text' => 'Witel', 'callback_data' => 'pic.set_start.cancel']
        ]);
    }

    public static function onSetStart($callbackValue, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $reqData = New RequestData();

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->text = $callbackValue;

        return Request::sendMessage($reqData->build());
    }
}