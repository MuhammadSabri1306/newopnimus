<?php
namespace App\TelegramRequest\AlertStatus;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextExclusionNotProvided extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        return TelegramText::create('Pengajuan penambahan')
            ->addBold(' Alerting Opnimus ')
            ->addText('hanya tersedia untuk grup.');
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}