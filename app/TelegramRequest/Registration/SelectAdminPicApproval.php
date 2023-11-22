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
        $this->buildText();
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

        $text = TelegramText::create()
            ->addBold('Registrasi PIC OPNIMUS')->newLine(2)
            ->addText('Terdapat permintaan registrasi User untuk ')->addBold('menjadi PIC')->addText(' sesuai data berikut.')->newLine(2)
            ->startCode()
            ->addText("Nama Pengguna   : $apprvData->full_name")->newLine()
            ->addText("No. Handphone   : $apprvData->telp")->newLine()
            ->addText('Level           : '.ucfirst($apprvData->level))->newLine();
        
        if($apprvData->level == 'regional' || $apprvData->level == 'witel') {
            $regional = Regional::find($apprvData->regional_id);
            $text->addText('Regional        : '.$regional['name'])->newLine();
        }
        
        if($apprvData->level == 'witel') {
            $witel = Witel::find($apprvData->witel_id);
            $text->addText('Witel           : '.$witel['witel_name'])->newLine();
        }

        $text->addText("NIK             : $apprvData->nik")->newLine()
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
        $this->setData('regist_data', $registData);
    }

    public function setRegional($regional)
    {
        $this->setData('regional', $regional);
    }

    public function setWitel($witel)
    {
        $this->setData('witel', $witel);
    }

    public function setLocations($locations)
    {
        $this->setData('locations', $locations);
    }

    public function buildText()
    {
        $this->params->text = $this->getText()->get();
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