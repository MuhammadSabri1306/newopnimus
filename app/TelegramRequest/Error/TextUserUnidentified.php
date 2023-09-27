<?php
namespace App\TelegramRequest\Error;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextUserUnidentified extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText();
    }

    public function getText()
    {
        return TelegramText::create('Anda belum terdaftar sebagai pengguna OPNIMUS.')->newLine()
            ->addText('Anda dapat mengetikkan perintah /start untuk melakukan registrasi sebagai pengguna.');
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}