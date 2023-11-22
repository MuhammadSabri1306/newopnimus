<?php
namespace App\TelegramRequest\AlertStatus;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextFeatureNotProvided extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        return TelegramText::create('Fitur')->addBold(' Alarm ')->addText('hanya tersedia untuk')
            ->addBold(' Grup ')->addText('dan')->addBold(' User PIC')->addText('. Terima kasih.');
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}