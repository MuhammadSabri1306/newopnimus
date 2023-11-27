<?php
namespace App\TelegramRequest\RegistrationAdmin;

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

        $text = TelegramText::create()
            ->startBold()->addText('Registrasi Admin OPNIMUS')->endBold()->newLine(2)
            ->addText('Terdapat pengajuan untuk menjadi Admin dengan data berikut.')->newLine(2)
            ->startCode();

        $username = $userData->username ?? '-';
        $text->addText("Username        : $username")->newLine();
        
        $fullName = '-';
        if($userData->first_name && $userData->last_name) $fullName = "$userData->first_name $userData->last_name";
        elseif($userData->first_name) $fullName = $userData->first_name;
        elseif($userData->last_name) $fullName = $userData->last_name;
        $text->addText("Nama User       : $fullName")->newLine();
        
        $text->addText('Level           : '.ucfirst($userData->level))->newLine();
        
        if($regional && ($userData->level == 'regional' || $userData->level == 'witel')) {
            $text->addText('Regional        : '.$regional['name'])->newLine();
        }
        
        if($witel && $userData->level == 'witel') {
            $text->addText('Witel           : '.$witel['witel_name'])->newLine();
        }

        $text->addText("NIK             : $userData->nik")->newLine();
        $text->addText('Status Karyawan : '.($userData->is_organik ? 'Organik' : 'Non Organik'))->newLine();

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
        $this->setData('regional', $regional);
        $this->params->text = $this->getText()->get();
    }

    public function setWitel($witel)
    {
        $this->setData('witel', $witel);
        $this->params->text = $this->getText()->get();
    }

    public function setInKeyboard(callable $callButton)
    {
        $inKeyboardItem = $callButton([
            'approve' => ['text' => '👍 Setujui', 'callback_data' => null],
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