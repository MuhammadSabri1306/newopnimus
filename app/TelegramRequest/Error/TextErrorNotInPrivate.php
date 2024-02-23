<?php
namespace App\TelegramRequest\Error;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextErrorNotInPrivate extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $botUsername = \App\Config\BotConfig::$BOT_USERNAME;
        return TelegramText::create()
            ->addText('Mohon maaf, permintaan tidak dapat dilakukan melalui grup.')
            ->addText('Silahkan melakukan private chat/japri langsung ke Bot.');
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}