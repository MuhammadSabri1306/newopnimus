<?php
namespace App\TelegramRequest\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectAdminPicApproval extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $registData = $this->getData('regist_data', null);
        $regional = $this->getData('regional', null);
        $witel = $this->getData('witel', null);
        $locations = $this->getData('locations', []);
        
        if(!$registData) {
            return TelegramText::create();
        }

        $apprvData = (object) $registData;

        $text = TelegramText::create()
            ->addBold('Registrasi PIC OPNIMUS')->newLine(2)
            ->addText('Terdapat permintaan registrasi User ')
            ->addMentionByUsername($apprvData->user_id, "@$apprvData->username")
            ->addText(' untuk ')
            ->addBold('menjadi PIC')->addText(' sesuai data berikut.')->newLine(2)
            ->startCode()
            ->addText("Nama Pengguna   : $apprvData->full_name")->newLine()
            ->addText("No. Handphone   : $apprvData->telp")->newLine()
            ->addText("NIK             : $apprvData->nik")->newLine()
            ->addText('Status Karyawan : '.($apprvData->is_organik ? 'Organik' : 'Non Organik'))->newLine()
            ->addText("Nama Instansi   : $apprvData->instansi")->newLine()
            ->addText("Unit Kerja      : $apprvData->unit")
            ->endCode()->newLine(2)
            ->addBold('Permintaan Lokasi :');

        foreach($locations as $loc) {
            $locName = $loc['location_name'];
            $locSname = $loc['location_sname'];
            $text->newLine()->addSpace(4)->addText("- $locSname ($locName)");
        }
        
        return $text;
    }

    public function setRegistrationData($registData)
    {
        if(is_array($registData)) {
            $this->setData('regist_data', $registData);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setRegional($regional)
    {
        if(is_array($regional)) {
            $this->setData('regional', $regional);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setWitel($witel)
    {
        if(is_array($witel)) {
            $this->setData('witel', $witel);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setLocations($locations)
    {
        if(is_array($locations)) {
            $this->setData('locations', $locations);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setInKeyboard(callable $callButton)
    {
        $inKeyboardItem = $callButton([
            'approve' => ['text' => '👍 Izinkan', 'callback_data' => null],
            'reject' => ['text' => '❌ Tolak', 'callback_data' => null]
        ]);

        $inlineKeyboardData = [ $inKeyboardItem['approve'], $inKeyboardItem['reject'] ];
        $this->params->replyMarkup = new InlineKeyboard($inlineKeyboardData);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}