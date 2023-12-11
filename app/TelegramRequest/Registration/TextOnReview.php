<?php
namespace App\TelegramRequest\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextOnReview extends TelegramRequest
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
        $hasRegionalData = $regional ? true : false;
        if($hasRegionalData) {
            $hasRegionalData = $registration['data']['level'] == 'regional' || $registration['data']['level'] == 'witel';
        }
        
        $witel = $this->getData('witel', null);
        $hasWitelData = $witel ? true : false;
        if($hasWitelData) {
            $hasWitelData = $registration['data']['level'] == 'witel';
        }


        if(!$registration) {
            return TelegramText::create('Terdapat error saat akan menyimpan data anda. Silahkan coba beberapa saat lagi.');
        }
        
        $text = TelegramText::create()
            ->addText('Terima kasih, grup akan didaftarkan sesuai data berikut.')->newLine(2)
            ->startCode();

        if($registration['data']['type'] == 'private') {
            $text->addText('Nama Pengguna   : '.$registration['data']['full_name'])->newLine();
            $text->addText('No. Handphone   : '.$registration['data']['telp'])->newLine();
        } else {
            $text->addText('Nama Grup       : '.$registration['data']['username'])->newLine();
        }
        
        $text->addText('Level           : '.ucfirst($registration['data']['level']))->newLine();
        
        if($hasRegionalData) {
            $text->addText('Regional        : '.$regional['name'])->newLine();
        }
        
        if($hasWitelData) {
            $text->addText('Witel           : '.$witel['witel_name'])->newLine();
        }
        
        if($registration['data']['type'] == 'private') {
            $text->addText('NIK             : '.$registration['data']['nik'])->newLine();
            $text->addText('Status Karyawan : '.( $registration['data']['is_organik'] ? 'Organik' : 'Non Organik' ))->newLine();
            $text->addText('Nama Instansi   : '.$registration['data']['instansi'])->newLine();
            $text->addText('Unit Kerja      : '.$registration['data']['unit'])->newLine();
        } else {
            $text->addText('Deskripsi Grup  : '.$registration['data']['group_description'])->newLine();
        }

        $text->endCode()->newLine()
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

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}