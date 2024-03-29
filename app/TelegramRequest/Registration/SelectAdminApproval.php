<?php
namespace App\TelegramRequest\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectAdminApproval extends TelegramRequest
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
        
        if(!$registration) {
            return TelegramText::create();
        }

        $registration = (object) $registration;
        $userData = isset($registration->data) ? (object) $registration->data : null;
        $isPrivateChat = $userData->type == 'private';

        $text = TelegramText::create()
            ->startBold()->addText('Registrasi User OPNIMUS')->endBold();
        $text->newLine(2)->addText('Terdapat permintaan registrasi ');
        if($isPrivateChat) {
            $text->addText('User ')->addMentionByUsername($registration->user_id, "@$userData->username");
        } else {
            $text->addText('Grup');
        }
        $text->addText(' dengan data berikut.')
            ->newLine(2)
            ->startCode();

        if($isPrivateChat) {
            $text->addText("Nama Pengguna   : $userData->full_name")->newLine();
            $text->addText("No. Handphone   : $userData->telp")->newLine();
        } else {
            $text->addText("Nama Grup       : $userData->username")->newLine();
        }
        
        $text->addText('Level           : '.ucfirst($userData->level))->newLine();
        
        if($userData->level == 'regional' || $userData->level == 'witel') {
            if(is_array($regional)) {
                $text->addText('Regional        : '.$regional['name'])->newLine();
            }
        }
        
        if($userData->level == 'witel') {
            if(is_array($witel)) {
                $text->addText('Witel           : '.$witel['witel_name'])->newLine();
            }
        }
        
        if(!$isPrivateChat) {
            $text->addText("Deskripsi Grup  : $userData->group_description")->newLine();
        } else {
            $text->addText("NIK             : $userData->nik")->newLine();
            $text->addText('Status Karyawan : '.($userData->is_organik ? 'Organik' : 'Non Organik'))->newLine();
            $text->addText("Nama Instansi   : $userData->instansi")->newLine();
            $text->addText("Unit Kerja      : $userData->unit")->newLine();
        }

        $text->endCode();
        return $text;
    }

    public function setRegistrationData($registration)
    {
        $this->setData('registration', $registration);
        $this->params->text = $this->getText()->get();
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