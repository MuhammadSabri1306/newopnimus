<?php
namespace App\TelegramRequest\RegistPic;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectTouApproval extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $user = $this->getData('user', null);
        $text = TelegramText::create();
        if(!$user) return $text;

        if(!$user['is_pic']) {
            $text->addBold('Anda akan mendaftarkan diri anda menjadi PIC Lokasi.')->newLine()
                ->addText('Silahkan memanfaatkan fitur ini apabila anda merupakan pengawal perangkat Network Element Telkom Indonesia di lokasi tertentu.')
                ->newLine(2);
        } else {
            $text->addBold('Anda akan memperbaharui Lokasi PIC anda.')->newLine();
        }

        if(count($user['locations']) < 1) {
            $text->addItalic('Saat ini anda belum menjadi PIC di lokasi manapun.')->newLine(2);
        } else {
            $text->addText('Saat ini anda terdaftar sebagai PIC di lokasi berikut.')->newLine();
            foreach($user['locations'] as $loc) {
                $locName = $loc['location_name'];
                $locSname = $loc['location_sname'];
                $text->newLine()->addItalic("- $locName ($locSname)");
            }
            $text->newLine(2);
        }

        $text->addText('Dengan terdaftar sebagai PIC lokasi, anda akan mendapatkan:')->newLine()
            ->addText('📌 ')->startBold()->addText('Alert khusus di lokasi yang anda kawal via japrian OPNIMUS, dan')->endBold()->newLine()
            ->addText('📌 ')->startBold()->addText('Tagging nama anda di grup agar tidak ada alarm yang terlewat dan memudahkan respon.')->endBold()->newLine();
        
        return $text;
    }

    public function setUser($telgUser)
    {
        if(is_array($telgUser)) {
            $this->setData('user', $telgUser);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setInKeyboard(callable $callButton)
    {
        $inlineKeyboardData = $callButton([
            'agree' => ['text' => 'Lanjutkan', 'callback_data' => null],
            'disagree' => ['text' => 'Batalkan', 'callback_data' => null]
        ]);

        $this->params->replyMarkup = new InlineKeyboard([
            $inlineKeyboardData['agree'],
            $inlineKeyboardData['disagree']
        ]);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}