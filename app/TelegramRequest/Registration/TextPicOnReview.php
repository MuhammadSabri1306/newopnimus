<?php
namespace App\TelegramRequest\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextPicOnReview extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $registration = $this->getData('registration', null);
        $regional = $this->getData('regional', null);
        $witel = $this->getData('witel', null);
        $locations = $this->getData('locations', null);

        if(!$registration) {
            return TelegramText::create('Terdapat error saat akan menyimpan data anda. Silahkan coba beberapa saat lagi.');
        }

        $text = TelegramText::create()
            ->addText('Terima kasih, anda akan didaftarkan sebagai PIC sesuai data berikut.')->newLine(2)
            ->startCode();
            
        $text->addText('Nama Pengguna   : '.$registration['data']['full_name'])->newLine();
        $text->addText('No. Handphone   : '.$registration['data']['telp'])->newLine();
        $text->addText('Level           : '.ucfirst($registration['data']['level']))->newLine();

        if($regional) $text->addText('Regional        : '.$regional['name'])->newLine();
        if($witel) $text->addText('Witel           : '.$witel['witel_name'])->newLine();

        $text->newLine();
        $text->addText('NIK             : '.$registration['data']['nik'])->newLine();
        $text->addText('Status Karyawan : '.($registration['data']['is_organik'] ? 'Organik' : 'Non Organik'))->newLine();
        $text->addText('Nama Instansi   : '.$registration['data']['instansi'])->newLine();
        $text->addText('Unit Kerja      : '.$registration['data']['unit']);

        if(is_array($locations)) {
            $text->newLine(2)->addText('Lokasi PIC      : ');
            foreach($locations as $loc) {
                $locName = $loc['location_name'];
                $locSname = $loc['location_sname'];
                $text->newLine()->addSpace(4)->addText("- $locSname ($locName)");
            }
        }

        $text->endCode()->newLine(2)
            ->addText('Silahkan menunggu Admin untuk melakukan verifikasi terhadap permintaan anda, terima kasih.')->newLine(2)
            ->startItalic()->addText('OPNIMUS, Stay Alert, Stay Safe')->endItalic();
        
        return $text;
    }

    public function setRegistration($registration)
    {
        if(is_array($registration)) {
            $this->setData('registration', $registration);
        }
        $this->params->text = $this->getText()->get();
    }

    public function setRegional($regional)
    {
        if(is_array($regional)) {
            $this->setData('regional', $regional);
        }
        $this->params->text = $this->getText()->get();
    }

    public function setWitel($witel)
    {
        if(is_array($witel)) {
            $this->setData('witel', $witel);
        }
        $this->params->text = $this->getText()->get();
    }

    public function setLocations($locations)
    {
        if(is_array($locations)) {
            $this->setData('locations', $locations);
        }
        $this->params->text = $this->getText()->get();
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}