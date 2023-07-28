<?php
namespace App\Controller\Bot;

use App\Core\DB;
use App\Core\RequestData;
use App\Controller\BotController;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;

class UserController extends BotController
{
    public static function checkRegistStatus(): ServerResponse
    {
        $message = UserController::$command->getMessage();
        $reqData = New RequestData();

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->replyToMessageId = $message->getMessageId();

        $chatType = $message->getChat()->getType();
        $fullName = ($chatType=='group' || $chatType=='supergroup') ? 'Grup '.$message->getChat()->getTitle()
            : $message->getFrom()->getFirstName().' '.$message->getFrom()->getLastName();
            
        $db = new DB();
        $userCount = $db->queryFirstField("SELECT COUNT(*) FROM telegram_user WHERE chat_id=%s", $reqData->chatId);
        
        if($userCount > 0) {

            $reqData->animation = 'https://giphy.com/gifs/transformers-optimus-prime-transformer-transformers-rise-of-the-beasts-Bf3Anv7HuOPHEPkiOx';
            $reqData->caption = "*$fullName sudah terdaftar dalam OPNIMUS:* \n\n silahkan pilih /help untuk petunjuk lebih lanjut.";
            return Request::sendAnimation($reqData->build());
            
        }
        
        $reqData->animation = 'https://giphy.com/gifs/transformers-optimus-prime-transformer-transformers-rise-of-the-beasts-Bf3Anv7HuOPHEPkiOx';
        $reqData->caption = "*$fullName belum terdaftar dalam OPNIMUS:* \n\n silahkan pilih /help untuk petunjuk lebih lanjut.";
        return Request::sendAnimation($reqData->build());
    }
}