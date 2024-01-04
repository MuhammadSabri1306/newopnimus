<?php
namespace App\TelegramRequest\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextPicApproved extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $telgUser = $this->setData('user', null);
        if(!$telgUser) {
            return TelegramText::create();
        }

        $approvedDate = $telgUser['updated_at'] ?? $telgUser['created_at'];
        
        $text = TelegramText::create()
            ->addBold('Pengajuan Lokasi PIC berhasil.')->newLine()
            ->addItalic($approvedDate)->newLine(2)
            ->addText('Proses pengajuan anda telah mendapat persetujuan Admin.')
            ->addText(' Dengan ini, anda telah merupakan PIC di lokasi berikut.');

        foreach($telgUser['locations'] as $loc) {
            $locName = $loc['location_name'];
            $locSname = $loc['location_sname'];
            $text->newLine()->addSpace(4)->addText("- $locSname ($locName)");
        }

        $text->newLine(2)->addText('Terima kasih.')->newLine()
            ->addText('OPNIMUS, Stay Alert, Stay Safe ')->newLine(2)
            ->addText('#PeduliInfrastruktur #PeduliCME');
        
        return $text;
    }

    public function setUser($telgUser)
    {
        if(is_array($telgUser)) {
            $this->setData('user', $telgUser);
            $this->params->text = $this->getText()->get();
        }
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}