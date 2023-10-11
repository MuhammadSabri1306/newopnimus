<?php
namespace App\TelegramRequest\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectTouApproval extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        return TelegramText::create('Sebelum mendaftar OPNIMUS apakah anda setuju dengan Ketentuan Penggunaan diatas?');
    }

    public function setInKeyboard(callable $callButton)
    {
        $inKeyboardItem = $callButton([
            'approve' => ['text' => '👍 Setuju', 'callback_data' => null],
            'reject' => ['text' => '❌ Tidak', 'callback_data' => null]
        ]);

        $inlineKeyboardData = [ $inKeyboardItem['approve'], $inKeyboardItem['reject'] ];
        $this->params->replyMarkup = new InlineKeyboard($inlineKeyboardData);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}