<?php
namespace App\TelegramRequest\RegistrationAdmin;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectRegistContinue extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        return TelegramText::create('Anda akan mengajukan diri sebagai')
            ->addBold(' Admin Opnimus ')->addText('. Lanjutkan?');
    }

    public function setInKeyboard(callable $callButton)
    {
        $inKeyboardItem = $callButton([
            'continue' => ['text' => '👍 Lanjutkan', 'callback_data' => null],
            'cancel' => ['text' => '❌ Batalkan', 'callback_data' => null]
        ]);

        $inlineKeyboardData = [ $inKeyboardItem['continue'], $inKeyboardItem['cancel'] ];
        $this->params->replyMarkup = new InlineKeyboard($inlineKeyboardData);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}