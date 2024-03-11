<?php
namespace App\TelegramRequest\ManagementUser;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectRemovePicApproval extends TelegramRequest
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
        $locations = $this->getData('locations', null);
        
        if(!$telgUser || !$telgPersUser) {
            return TelegramText::create();
        }

        $text = TelegramText::create('Anda akan me-reset PIC ')
            ->addMentionByUsername($telgUser->user_id, "@$telgUser->username")
            ->addText(' dengan data berikut.')
            ->newLine(2)
            ->startCode();

        $text->addText("Nama Pengguna   : $telgPersUser->nama")->newLine();
        $text->addText("No. Handphone   : $telgPersUser->telp")->newLine();
        $text->addText('Level           : '.ucfirst($telgUser->level))->newLine();

        if($regional) $text->addText("Regional        : $regional->name")->newLine();
        if($witel) $text->addText("Witel           : $witel->witel_name")->newLine();

        $text->newLine();
        $text->addText("NIK             : $telgPersUser->nik")->newLine();
        $text->addText('Status Karyawan : '.($telgPersUser->is_organik ? 'Organik' : 'Non Organik'))->newLine();
        $text->addText("Nama Instansi   : $telgPersUser->instansi")->newLine();
        $text->addText("Unit Kerja      : $telgPersUser->unit");

        if(is_array($locations)) {
            $text->newLine(2)->addText('Lokasi PIC      : ');
            foreach($locations as $loc) {
                $locName = $loc['location_name'];
                $locSname = $loc['location_sname'];
                $text->newLine()->addSpace(4)->addText("- $locSname ($locName)");
            }
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

    public function setLocations($locations)
    {
        if(is_array($locations)) {
            $this->setData('locations', (object) $locations);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setInKeyboard(callable $callButton)
    {
        $inKeyboardItem = $callButton([
            'approve' => [ 'text' => '❎ Reset PIC', 'callback_data' => null ],
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