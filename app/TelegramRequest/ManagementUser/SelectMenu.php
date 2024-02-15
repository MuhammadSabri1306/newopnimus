<?php
namespace App\TelegramRequest\ManagementUser;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectMenu extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        return TelegramText::create('Silahkan pilih menu berikut.');
    }

    public function setInKeyboard(callable $callButton)
    {
        $inKeyboardData = $callButton([
            'removeUser' => ['text' => 'Hapus User', 'callback_data' => null],
            'removePic' => ['text' => 'Hapus PIC', 'callback_data' => null],
            'removeAdmin' => ['text' => 'Hapus Admin', 'callback_data' => null],
            'assignPic' => ['text' => 'Assign PIC', 'callback_data' => null],
        ]);

        $inKeyboard = [
            [ $inKeyboardData['removeUser'] ],
            [ $inKeyboardData['removePic'] ],
            [ $inKeyboardData['removeAdmin'] ],
            [ $inKeyboardData['assignPic'] ],
        ];

        $this->params->replyMarkup = new InlineKeyboard(...$inKeyboard);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}