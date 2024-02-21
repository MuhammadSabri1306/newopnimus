<?php
namespace App\TelegramRequest\CheckRtu;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextRtuDetail extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $regional = $this->getData('regional', null);
        $witel = $this->getData('witel', null);
        $loc = $this->getData('location', null);
        $rtu = $this->getData('rtu', null);

        $invalidData = array_filter([ $regional, $witel, $loc, $rtu ], fn($val) => is_null($val));
        if(count($invalidData) > 0) {
            return TelegramText::create();
        }

        if(is_array($regional)) $regional = (object) $regional;
        if(is_array($witel)) $witel = (object) $witel;
        if(is_array($loc)) $loc = (object) $loc;

        return TelegramText::create()
            ->addBold("🗂 Detail $rtu->sname ($loc->location_sname)")->newLine(2)
            ->startCode()
            ->addText("🏛 Regional   : $regional->name")->newLine()
            ->addText("🏢 Witel      : $witel->witel_name")->newLine()
            ->addText("🏕 Lokasi     : $loc->location_name")->newLine(2)
            ->addText("🎰 Kode RTU   : $rtu->sname")->newLine()
            ->addText("🔠 Nama RTU   : $rtu->name")->newLine()
            ->addText("📡 IP RTU     : $rtu->ip_address")->newLine()
            ->addText("🔔 Status RTU : $rtu->status")->newLine()
            ->endCode();
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

    public function setLocation($loc)
    {
        if(is_array($loc)) {
            $this->setData('location', $loc);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setRtu($rtu)
    {
        if($rtu) {
            $this->setData('rtu', $rtu);
            $this->params->text = $this->getText()->get();
        }
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}