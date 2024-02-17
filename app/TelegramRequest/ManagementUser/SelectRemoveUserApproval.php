<?php
namespace App\TelegramRequest\ManagementUser;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectRemoveUserApproval extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $telgUser = $this->getData('telegram_user', null);
        $telgPersUser = $this->getData('telegram_personal_user', null);
        $regional = $this->getData('regional', null);
        $witel = $this->getData('witel', null);
        
        if(!$telgUser) {
            return TelegramText::create();
        }

        $text = TelegramText::create('Anda akan menghapus ');
        $isPrivateChat = $telgUser->type == 'private';

        if($isPrivateChat) {
            $text->addText('User ')->addMentionByUsername($telgUser->user_id, "@$telgUser->username");
        } else {
            $text->addText('Grup');
        }
        $text->addText(' dengan data berikut.')
            ->newLine(2)
            ->startCode();

        if(!$isPrivateChat) {
            $text->addText("Nama Grup       : $telgUser->username")->newLine();
        } elseif($telgPersUser) {
            $text->addText("Nama Pengguna   : $telgPersUser->full_name")->newLine();
            $text->addText("No. Handphone   : $telgPersUser->telp")->newLine();
        }

        $text->addText('Level           : '.ucfirst($telgUser->level))->newLine();

        if($telgUser->level == 'regional' || $telgUser->level == 'witel') {
            if($regional) {
                $text->addText("Regional        : $regional->name")->newLine();
            }
        }

        if($telgUser->level == 'witel' && $witel) {
            $text->addText("Witel           : $witel->witel_name")->newLine();
        }
        
        if($isPrivateChat) {
            $text->addText('Status PIC      : ' . ($telgUser->is_pic ? 'Ya' : 'Bukan'))->newLine();
        }

        if(!$isPrivateChat) {
            $text->addText("Deskripsi Grup  : $telgUser->group_description")->newLine();
        } elseif($telgPersUser) {
            $text->addText("NIK             : $telgPersUser->nik")->newLine();
            $text->addText('Status Karyawan : '.($telgPersUser->is_organik ? 'Organik' : 'Non Organik'))->newLine();
            $text->addText("Nama Instansi   : $telgPersUser->instansi")->newLine();
            $text->addText("Unit Kerja      : $telgPersUser->unit")->newLine();
        }

        $text->endCode();
        return $text;
    }

    public function setTelegramUser($telgUser)
    {
        if(is_array($telgUser)) {
            $this->setData('telegram_user', (object) $telgUser);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setTelegramPersonalUser($telgPersUser)
    {
        if(is_array($telgPersUser)) {
            $this->setData('telegram_personal_user', (object) $telgPersUser);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setRegional($regional)
    {
        if(is_array($regional)) {
            $this->setData('regional', (object) $regional);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setWitel($witel)
    {
        if(is_array($witel)) {
            $this->setData('witel', (object) $witel);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setInKeyboard(callable $callButton)
    {
        $inKeyboardItem = $callButton([
            'approve' => ['text' => '❎ Hapus User', 'callback_data' => null],
            'reject' => ['text' => 'Batalkan', 'callback_data' => null]
        ]);

        $inlineKeyboardData = [ $inKeyboardItem['approve'], $inKeyboardItem['reject'] ];
        $this->params->replyMarkup = new InlineKeyboard($inlineKeyboardData);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}