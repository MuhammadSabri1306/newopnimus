<?php
namespace App\TelegramRequest\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectLevel extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        return TelegramText::create()
            ->addText('Terima kasih. Silahkan memilih ')->startBold()->addText('Level Monitoring')->endBold()->addText('.')->newLine(2)
            ->startItalic()->addText('* Pilih Witel Apabila anda Petugas CME/Teknisi di Lokasi Tertentu')->endItalic();
    }

    public function setInKeyboard(callable $callButton)
    {
        $inKeyboardItem = $callButton([
            'nasional' => ['text' => 'Nasional', 'callback_data' => null],
            'regional' => ['text' => 'Regional', 'callback_data' => null],
            'witel' => ['text' => 'Witel', 'callback_data' => null],
        ]);
        
        $this->params->replyMarkup = new InlineKeyboard(
            [
                $inKeyboardItem['nasional'],
            ], [
                $inKeyboardItem['regional'],
                $inKeyboardItem['witel']
            ]
        );
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}