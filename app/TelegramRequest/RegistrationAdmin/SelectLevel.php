<?php
namespace App\TelegramRequest\RegistrationAdmin;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectLevel extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        return TelegramText::create('Silahkan memilih Level Admin.');
    }

    public function setInKeyboard(callable $callButton)
    {
        $inKeyboardItem = $callButton([
            'nasional' => ['text' => 'Nasional', 'callback_data' => null],
            'regional' => ['text' => 'Regional', 'callback_data' => null],
            'witel' => ['text' => 'Witel', 'callback_data' => null]
        ]);

        $inlineKeyboardData = [
            [ $inKeyboardItem['nasional'] ],
            [ $inKeyboardItem['regional'], $inKeyboardItem['witel'] ]
        ];
        $this->params->replyMarkup = new InlineKeyboard(...$inlineKeyboardData);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}