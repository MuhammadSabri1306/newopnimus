<?php
namespace App\TelegramRequest\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextPicUpdateOnReview extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $telgPersUser = $this->getData('telegram_personal_user', null);
        $locations = $this->getData('locations', null);

        if(!$telgPersUser) {
            return TelegramText::create('Terdapat error saat akan menyimpan data anda. Silahkan coba beberapa saat lagi.');
        }

        $text = TelegramText::create()
            ->addText('Terima kasih, lokasi PIC anda akan diupdate sesuai data berikut.')->newLine(2)
            ->startCode();
            
        $text->addText('Nama Pengguna   : '.$telgPersUser['nama'])->newLine();
        $text->addText('No. Handphone   : '.$telgPersUser['telp'])->newLine();
        $text->addText('NIK             : '.$telgPersUser['nik'])->newLine();
        $text->addText('Status Karyawan : '.($telgPersUser['is_organik'] ? 'Organik' : 'Non Organik'))->newLine();
        $text->addText('Nama Instansi   : '.$telgPersUser['instansi'])->newLine();
        $text->addText('Unit Kerja      : '.$telgPersUser['unit']);

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

    public function setTelegramPersonalUser($telgPersUser)
    {
        if(is_array($telgPersUser)) {
            $this->setData('telegram_personal_user', $telgPersUser);
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