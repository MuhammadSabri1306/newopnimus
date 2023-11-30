<?php
namespace App\TelegramRequest\Port;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;
use App\Core\TelegramTextSplitter;

useHelper('number');
useHelper('date');

class ListTextCheckPort extends TelegramRequest
{
    use TelegramTextSplitter;

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
                $portIcon = $this->formatIcon($port->severity->name);
                $portValue = $this->formatPortValue($port);
                $portStatus = $port->severity->name;

                $text->newLine()
                    ->addSpace()
                    ->addText("$portIcon ($port->no_port) $port->description ($portValue) status $portStatus");
            }

            $text->endCode();
        }

        return $text;
    }

    protected function formatIcon(string $severityName)
    {
        $statusKey = strtoupper($severityName);
        if($statusKey == 'NORMAL') return '✅';
        if($statusKey == 'OFF') return '‼️';
        if($statusKey == 'CRITICAL') return '❗️';
        if($statusKey == 'WARNING') return '⚠️';
        if($statusKey == 'SENSOR BROKEN') return '❌';
        return '';
    }

    protected function formatPortValue($port)
    {
        $portUnitKey = strtoupper($port->units);

        if(in_array($portUnitKey, ['OFF', 'ON/OFF'])) {
            return boolval($port->value) ? 'OFF' : 'ON';
        }

        if($portUnitKey == 'OPEN/CLOSE') {
            return boolval($port->value) ? 'OPEN' : 'CLOSE';
        }
        
        if(is_null($port->value)) {
            return 'null';
        }

        $value = convertToNumber($port->value);
        $unit = $port->units;

        $unit = utf8_encode($unit);
        $value = utf8_encode($value);
        
        if(in_array($portUnitKey, ['#', '%', '%RH', '-'])) {
            return $value.$unit;
        }

        return "$value $unit";
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
        return Request::sendMessage($this->params->build());
    }

    public function sendList(callable $beforeSend = null): ServerResponse
    {
        $text = $this->params->text;
        $messageTextList = $this->splitText($text, 30);

        $params = $this->params;
        $serverResponse = Request::emptyResponse();

        foreach($messageTextList as $messageText) {

            if(is_callable($beforeSend)) {
                $beforeSend();
            }

            $params->text = $messageText;
            try {

                $response = Request::sendMessage($params->build());
                $serverResponse = $response;

            } catch(\Throwable $err) {
                \MuhammadSabri1306\MyBotLogger\Entities\ErrorLogger::catch($err);
            }

        }

        return $serverResponse;
    }
}