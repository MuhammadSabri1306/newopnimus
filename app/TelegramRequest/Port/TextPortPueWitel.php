<?php
namespace App\TelegramRequest\Port;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;
use App\Core\TelegramRequest\TextList;
use App\Core\TelegramRequest\PortFormat;
use App\Core\TelegramRequest\PueRanges;

class TextPortPueWitel extends TelegramRequest
{
    use TextList, PortFormat, PueRanges;

    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $ports = $this->getData('ports', []);
        $witel = $this->getData('witel', null);
        $regional = $this->getData('regional', null);
        $currDateTime = date('Y-m-d H:i:s');

        if(!$witel || !$regional) {
            return TelegramText::create();
        }

        $text = TelegramText::create()
            ->addText('🧾Berikut daftar Port PUE yang terdapat pada ')
            ->addBold($witel['witel_name'] . ' ' . $regional['name'])
            ->addText(':')->newLine(2)
            ->addItalic("Data diambil pada: $currDateTime WIB")
            ->startCode();

        if(count($ports) < 1) {
            $text->newLine(2)->addItalic('TIDAK DITEMUKAN PORT PUE');
            return $text;
        }

        foreach($ports as $rtuSname => $port) {

            $pueCategoryKey = $this->getPueCategory($port->value);
            $pueCategory = strtoupper($pueCategoryKey);
            $pueIcon = $this->getPueIconByCategory($pueCategoryKey);
            $portValue = $this->toDefaultPortValueFormat($port->value, $port->units, $port->identifier);

            $text->newLine()
                ->addSpace()
                ->addText("$pueIcon $rtuSname $port->description ($portValue) status $pueCategory");
            
        }

        $text->endCode();
        return $text;
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

    public function setPorts(array $ports)
    {
        if(count($ports) > 0) {
            $rtuOrderedPorts = array_reduce($ports, function($list, $port) {
                if(strtolower($port->identifier) != 'pue') {
                    return $list;
                }
                $rtuSname = $port->rtu_sname;
                $list[$rtuSname] = $port;
                return $list;
            }, []);
    
            $this->setData('ports', $rtuOrderedPorts);
            $this->params->text = $this->getText()->get();
        }
    }

    public function send(): ServerResponse
    {
        $text = $this->params->text;
        $messageTextList = $this->splitText($text, 50);

        if(count($messageTextList) < 2) {
            return Request::sendMessage($this->params->build());
        }
        return $this->sendList($messageTextList);
    }
}