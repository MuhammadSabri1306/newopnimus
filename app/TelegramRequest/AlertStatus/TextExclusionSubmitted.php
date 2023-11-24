<?php
namespace App\TelegramRequest\AlertStatus;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextExclusionSubmitted extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $groupName = $this->getData('group_name', null);
        if(!$groupName) {
            return TelegramText::create();
        }

        return TelegramText::create('Pengajuan penambahan')->addBold(' Alerting Opnimus ')
            ->addText('untuk grup ini telah diteruskan ke Super Admin.')->newLine(2)
            ->addText('Apabila perlu konfirmasi lebih detail, anda dapat menghubungi ')
            ->addMentionByUsername('212163935', '@Vayliant')
            ->addText('. Terima kasih.')->newLine(2)
            ->addItalic('"OPNIMUS 2.0 -Stay Alert, Stay Safe"');
    }

    public function setGroupName($groupName)
    {
        $this->setData('group_name', $groupName);
        $this->params->text = $this->getText()->get();
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}