<?php
namespace App\TelegramRequest\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectIsOrganik extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        return TelegramText::create('Apakah anda berstatus sebagai karyawan organik?');
    }

    public function setInKeyboard(callable $callButton)
    {
        $inKeyboardData = $callButton([
            'yes' => ['text' => 'Ya', 'callback_data' => null],
            'no' => ['text' => 'Tidak', 'callback_data' => null],
        ]);
        
        $this->params->replyMarkup = new InlineKeyboard([ $inKeyboardData['yes'], $inKeyboardData['no'] ]);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}