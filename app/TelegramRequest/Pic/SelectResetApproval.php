<?php
namespace App\TelegramRequest\Pic;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectResetApproval extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $picLocs = $this->getData('locs', null);

        $text = TelegramText::create();
        if(!is_array($picLocs)) return $text;

        $text->addText('Anda akan me-reset status anda sebagai PIC.')->newLine();

        if(count($picLocs) < 1) {
            $text->addItalic('Saat ini anda belum menjadi PIC di lokasi manapun.')->newLine(2);
            return $text;
        }

        $text->addText('Saat ini anda terdaftar sebagai PIC di lokasi berikut.')->newLine();
        foreach($picLocs as $loc) {
            $locName = $loc['location_name'];
            $locSname = $loc['location_sname'];
            $text->newLine()->addItalic("- $locName ($locSname)");
        }
        
        return $text;
    }

    public function setLocations($picLocs)
    {
        if(is_array($picLocs)) {
            $this->setData('locs', $picLocs);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setInKeyboard(callable $callButton)
    {
        $inlineKeyboardData = $callButton([
            'continue' => ['text' => '❎ Reset PIC', 'callback_data' => null],
            'cancel' => ['text' => 'Batalkan', 'callback_data' => null]
        ]);

        $this->params->replyMarkup = new InlineKeyboard([
            $inlineKeyboardData['continue'],
            $inlineKeyboardData['cancel']
        ]);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}