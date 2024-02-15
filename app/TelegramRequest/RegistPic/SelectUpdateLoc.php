<?php
namespace App\TelegramRequest\RegistPic;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectUpdateLoc extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $locs = $this->getData('locs', []);
        $maxLocs = $this->getData('max_locs', null);

        $text = TelegramText::create('Berikut adalah lokasi yang akan dimonitor:');

        foreach($locs as $loc) {
            $locName = $loc['location_name'];
            $locSname = $loc['location_sname'];
            $text->newLine(2)->addItalic("- $locName ($locSname)");
        }

        if($maxLocs && $maxLocs > 0) {
            $text->newLine(2)->addItalic("Anda dapat memilih maksimal $maxLocs lokasi.");
        }

        return $text;
    }

    public function setLocations($locs, $max = null)
    {
        if(is_array($locs)) {
            $this->setData('locs', $locs);
            $this->setData('max_locs', $max);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setInKeyboard(callable $callButton)
    {
        $inKeyboard = $callButton([
            'next' => [ 'text' => 'Lanjutkan', 'callback_data' => null ],
            'add' => [ 'text' => 'Tambah', 'callback_data' => null ],
            'remove' => [ 'text' => 'Hapus', 'callback_data' => null ],
        ]);

        $countLocs = count( $this->getData('locs', []) );
        $maxLocs = $this->getData('max_locs', null);
        $inKeyboardData1 = [];
        $inKeyboardData2 = [];

        if($countLocs > 1) {
            array_push($inKeyboardData1, $inKeyboard['remove']);
        }

        if($countLocs < $maxLocs) {
            array_push($inKeyboardData1, $inKeyboard['add']);
        }

        if($countLocs > 0) {
            array_push($inKeyboardData2, $inKeyboard['next']);
        }

        $inKeyboardData = [];
        if(count($inKeyboardData1)) array_push($inKeyboardData, $inKeyboardData1);
        if(count($inKeyboardData2)) array_push($inKeyboardData, $inKeyboardData2);

        $this->params->replyMarkup = new InlineKeyboard(...$inKeyboardData);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}