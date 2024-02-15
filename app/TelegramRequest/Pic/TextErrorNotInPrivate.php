<?php
namespace App\TelegramRequest\Pic;

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
            ->addText('Mohon maaf, permintaan set PIC Lokasi tidak dapat dilakukan melalui grup. ')
            ->addText('Anda dapat melakukan private chat ')
            ->startBold()->addText('(japri)')->endBold()
            ->addText(" langsung ke bot @$botUsername dan mengetikkan perintah /setpic, terima kasih.");
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}