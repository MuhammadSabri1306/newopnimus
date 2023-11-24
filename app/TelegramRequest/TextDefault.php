<?php
namespace App\TelegramRequest;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextDefault extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $msgText = $this->getData('message_text', null);
        return $msgText ?? TelegramText::create();
    }

    public function setText(callable $setTextFunc)
    {
        $msgText = $setTextFunc(TelegramText::create());
        $this->setData('message_text', $msgText);
        $this->params->text = $this->getText()->get();
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