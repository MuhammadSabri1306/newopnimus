<?php
namespace App\TelegramRequest\Error;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextErrorNotFound extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        return TelegramText::create('Tidak dapat menemukan data terkait.');
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}