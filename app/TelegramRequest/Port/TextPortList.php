<?php
namespace App\TelegramRequest\Port;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;
use App\Core\TelegramRequest\TextList;
use App\Core\TelegramRequest\PortFormat;

useHelper('number');
useHelper('date');

class TextPortList extends TelegramRequest
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
        $groupedPorts = $this->getData('ports', []);
        $firstPort = $this->getData('first_port', null);
        $currDateTime = date('Y-m-d H:i:s');

        if(count($groupedPorts) < 1 || !$firstPort) {
            return TelegramText::create();
        }

        $text = TelegramText::create()
            ->addText('🧾Berikut daftar Port yang terdapat pada ')
            ->addBold("$firstPort->rtu_sname ($firstPort->location) $firstPort->witel $firstPort->regional")
            ->addText(':')->newLine(2)
            ->addItalic("Data Diambil pada: $currDateTime WIB");

        foreach($groupedPorts as $portName => $portList) {
            $text->newLine(2)
                ->addBold($portName)
                ->startCode();
            
            foreach($portList as $port) {
                $portSeverity = $port->severity->name;
                $portIcon = $this->getAlarmIcon($port->no_port, $port->port_name, $portSeverity);
                $portValue = $this->toDefaultPortValueFormat($port->value, $port->units, $port->identifier);

                $text->newLine()
                    ->addSpace()
                    ->addText("$portIcon ($port->no_port) $port->description ($portValue) status $portSeverity");
            }

            $text->endCode();
        }

        return $text;
    }

    public function setPorts(array $ports)
    {
        if(count($ports) > 0) {
            $groupedPorts = array_reduce($ports, function($group, $port) {
    
                $key = $port->port_name ?? "PORT $port->no_port";
                if(!isset($group[$key])) $group[$key] = [];
                array_push($group[$key], $port);
                return $group;
    
            }, []);
    
            $this->setData('ports', $groupedPorts);
            $this->setData('first_port', $ports[0]);
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