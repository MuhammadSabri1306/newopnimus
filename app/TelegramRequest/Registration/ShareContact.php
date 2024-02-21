<?php
namespace App\TelegramRequest\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class ShareContact extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();

        $keyboardButton = new KeyboardButton('Bagikan Kontak Saya');
        $keyboardButton->setRequestContact(true);
        $this->params->replyMarkup = ( new Keyboard($keyboardButton) )
                ->setOneTimeKeyboard(true)
                ->setResizeKeyboard(true)
                ->setSelective(true);
    }

    public function getText()
    {
        return TelegramText::create('Silahkan pilih menu "Bagikan Kontak Saya".');
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}