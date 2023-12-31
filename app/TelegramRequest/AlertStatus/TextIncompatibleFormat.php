<?php
namespace App\TelegramRequest\AlertStatus;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextIncompatibleFormat extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        return TelegramText::create('Format perintah tidak sesuai. Silahkan gunakan format berikut.')
            ->addCode('/alert [ON/OFF/STATUS]')->newLine(2)
            ->addItalic('Keterangan:')->newLine()
            ->addItalic(' - [ON] Menyalakan Alert,')->newLine()
            ->addItalic(' - [OFF] Mematikan Alert,')->newLine()
            ->addItalic(' - [STATUS] Info status Alert saat ini.');
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}