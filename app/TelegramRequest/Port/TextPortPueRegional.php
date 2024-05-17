<?php
namespace App\TelegramRequest\Port;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;
use App\Core\TelegramRequest\TextList;
use App\Core\TelegramRequest\PortFormat;

class TextPortPueRegional extends TelegramRequest
{
    use TextList, PortFormat;

    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }
    
    public function getText()
    {
        $witelPorts = $this->getData('ports', []);
        $regional = $this->getData('regional', null);
        $currDateTime = date('Y-m-d H:i:s');

        if(!$regional) {
            return TelegramText::create();
        }

        $text = TelegramText::create()
            ->addText('🧾Berikut daftar Port PUE yang terdapat pada ')
            ->addBold($regional['name'])
            ->addText(':')->newLine(2)
            ->addItalic("Data diambil pada: $currDateTime WIB");
        
        if(count($witelPorts) < 1) {
            $text->newLine(2)->addItalic('TIDAK DITEMUKAN PORT PUE');
            return $text;
        }

        foreach($witelPorts as $witelName => $ports) {

            $text->newLine(2)
                ->addBold($witelName)
                ->startCode();
            foreach($ports as $rtuSname => $port) {

                $portSeverity = $port->severity->name;
                $portIcon = $this->getAlarmIcon($port->no_port, $port->port_name, $portSeverity);
                $portValue = $this->toDefaultPortValueFormat($port->value, $port->units, $port->identifier);
                $text->newLine()
                    ->addSpace()
                    ->addText("$portIcon $rtuSname ($port->no_port) $port->description ($portValue) status $portSeverity");
            
            }
            $text->endCode();
            
        }

        return $text;
    }

    public function setRegional($regional)
    {
        if(is_array($regional)) {
            $this->setData('regional', $regional);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setPorts(array $ports)
    {
        if(count($ports) > 0) {
            $witelOrderedPorts = array_reduce($ports, function($list, $port) {
                if(strtolower($port->identifier) != 'pue') {
                    return $list;
                }

                $witelName = $port->witel;
                if(!isset($list[$witelName])) {
                    $list[$witelName] = [];
                }

                $rtuSname = $port->rtu_sname;
                $list[$witelName][$rtuSname] = $port;
                return $list;
            }, []);
    
            $this->setData('ports', $witelOrderedPorts);
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