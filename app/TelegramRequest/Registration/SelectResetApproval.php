<?php
namespace App\TelegramRequest\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectResetApproval extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        return TelegramText::create('Anda akan me-reset data anda dan keluar dari daftar pengguna OPNIMUS. Lanjutkan?');
    }

    public function setInKeyboard(callable $callButton)
    {
        $inKeyboardData = $callButton([
            'yes' => [ 'text' => '❎ Reset', 'callback_data' => null ],
            'no' => [ 'text' => 'Batalkan', 'callback_data' => null ],
        ]);
        
        $this->params->replyMarkup = new InlineKeyboard([ $inKeyboardData['yes'], $inKeyboardData['no'] ]);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}