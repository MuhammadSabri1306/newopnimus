<?php
namespace App\TelegramResponse\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramResponse;
use App\Core\RequestData;
use App\Core\TelegramText;

class PicReset extends TelegramResponse
{
    private $chatId;
    private $requestData;
    private $telgUser;

    public function __construct($chatId, array $telgUser)
    {
        $this->chatId = $chatId;
        $this->telgUser = $telgUser;
        $this->setRequest();
    }

    public function getText()
    {
        $user = $this->telgUser;
        $text = TelegramText::create('Anda akan me-reset status anda sebagai PIC.')->newLine();

        if(count($user['locations']) < 1) {
            $text->addItalic('Saat ini anda belum menjadi PIC di lokasi manapun.')->newLine(2);
        } else {
            $text->addText('Saat ini anda terdaftar sebagai PIC di lokasi berikut.')->newLine();
            foreach($user['locations'] as $loc) {
                $locName = $loc['location_name'];
                $locSname = $loc['location_sname'];
                $text->newLine()->addItalic("- $locName ($locSname)");
            }
            $text->newLine(2);
        }
        
        return $text;
    }

    public function setRequest(callable $callButton = null, callable $callRequest = null)
    {
        $reqData = new RequestData();
        $reqData->chatId = $this->chatId;
        $reqData->parseMode = 'markdown';
        $reqData->text = $this->getText()->get();

        $inlineKeyboardData = [
            'continue' => ['text' => 'Lanjutkan', 'callback_data' => null],
            'cancel' => ['text' => 'Batalkan', 'callback_data' => null]
        ];

        if(is_callable($callButton)) {
            $inlineKeyboardData = $callButton($inlineKeyboardData);
        }

        $reqData->replyMarkup = new InlineKeyboard([
            $inlineKeyboardData['continue'],
            $inlineKeyboardData['cancel']
        ]);

        $this->requestData = is_callable($callRequest) ? $callRequest($reqData) : $reqData;
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->requestData->build());
    }
}