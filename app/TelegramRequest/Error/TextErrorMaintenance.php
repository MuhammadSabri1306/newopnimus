<?php
namespace App\TelegramRequest\Error;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextErrorMaintenance extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        return TelegramText::create('Modul ini sedang dalam proses development untuk sementara waktu.');
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}