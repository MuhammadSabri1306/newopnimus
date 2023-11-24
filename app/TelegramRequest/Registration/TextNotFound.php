<?php
namespace App\TelegramRequest\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextNotFound extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        return TelegramText::create('Permintaan registrasi tidak dapat ditemukan.')
            ->addText(' Hal ini dapat dikarenakan permintaan telah dihapus.');
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }

    public function sendUpdate(): ServerResponse
    {
        return Request::editMessageText($this->params->build());
    }
}