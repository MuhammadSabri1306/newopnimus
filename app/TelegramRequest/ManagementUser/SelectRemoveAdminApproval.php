<?php
namespace App\TelegramRequest\ManagementUser;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectRemoveAdminApproval extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $admin = $this->getData('admin_data', null);
        $regional = $this->getData('regional', null);
        $witel = $this->getData('witel', null);
        
        if(!$admin) {
            return TelegramText::create();
        }

        $text = TelegramText::create('Anda akan menghapus Admin ')
            ->addMentionByUsername($admin->chat_id, "@$admin->username")
            ->addText(' dengan data berikut.')
            ->newLine(2)
            ->startCode();

        $fullName = $admin->full_name;
        if(empty($fullName)) {
            $fullName = implode(' ', array_filter([ $admin->first_name, $admin->last_name ]));
        }
        $text->addText("Nama User       : $fullName")->newLine();
        
        $text->addText("Level           : $admin->level")->newLine();
        if($regional) $text->addText("Regional        : $regional->name")->newLine();
        if($witel) $text->addText("Witel           : $witel->witel_name")->newLine();
        $text->addText("NIK             : $admin->nik")->newLine();
        $text->addText('Status Karyawan : '.($admin->is_organik ? 'Organik' : 'Non Organik'))->newLine();
        $text->addText("Instansi        : $admin->instansi")->newLine();
        $text->addText("Unit            : $admin->instansi")->newLine();

        $text->endCode();
        return $text;
    }

    public function setAdminData($adminData)
    {
        if(is_array($adminData)) {
            $this->setData('admin_data', (object) $adminData);
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
            'approve' => [ 'text' => '❎ Hapus Admin', 'callback_data' => null ],
            'reject' => [ 'text' => 'Batalkan', 'callback_data' => null ]
        ]);

        $inlineKeyboardData = [ $inKeyboardItem['approve'], $inKeyboardItem['reject'] ];
        $this->params->replyMarkup = new InlineKeyboard($inlineKeyboardData);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}