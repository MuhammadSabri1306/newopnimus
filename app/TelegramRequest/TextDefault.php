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
        return $msgText ? TelegramText::create($msgText) : TelegramText::create();
    }

    public function setText($arg)
    {
        if(is_callable($arg)) {
            $msgText = $arg(TelegramText::create())->get();
        } elseif(is_string($arg)) {
            $msgText = $arg;
        } else {
            throw new \Exception('1\'st args should be string or callable');
        }

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