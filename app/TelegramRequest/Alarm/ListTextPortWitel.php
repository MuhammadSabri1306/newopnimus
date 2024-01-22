<?php
namespace App\TelegramRequest\Alarm;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;
use App\Core\TelegramTextSplitter;

useHelper('area');
useHelper('number');
useHelper('date');

class ListTextPortWitel extends TelegramRequest
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
        $alarms = $this->getData('alarms', []);
        $witel = $this->getData('witel', null);
        $currDateTime = date('Y-m-d H:i:s');

        if(!$witel) {
            return TelegramText::create();
        }

        $text = TelegramText::create('Status alarm OSASE di ')
            ->addBold($witel['witel_name'])
            ->addText(" pada $currDateTime WIB adalah:");

        if(empty($alarms)) {
            $text->newLine(2)->addSpace(4)->addItalic('Belum ada Port RTU berstatus sebagai alarm');
            return $text;
        }

        foreach($alarms as $rtu) {
            $text->newLine(2)
                ->addBold("⛽️$rtu->rtu_sname ($rtu->location) :")
                ->startCode();

            foreach($rtu->ports as $port) {

                $portStatusTitle = $this->formatPortStatus($port);
                $portName = $port->description;
                $portNo = $port->no_port;
                $portValue = $this->formatPortValue($port);
                $duration = dateDiff(timeToDateString($port->updated_at), $currDateTime);

                $text->newLine()
                    ->addSpace(2)
                    ->addText("$portStatusTitle: ($portNo) $portName ($portValue)");
            }

            $text->endCode();
        }

        return $text;
    }

    protected function formatPortStatus($port)
    {
        $portName = $port->port_name ?? $port->no_port;
        if($portName == 'Status PLN') return '⚡️ PLN OFF';
        if($portName == 'Status DEG') return '🔆 GENSET ON';

        $statusKey = strtoupper($port->severity->name);
        if($statusKey == 'OFF') return '‼️'.$statusKey;
        if($statusKey == 'CRITICAL') return '❗️'.$statusKey;
        if($statusKey == 'WARNING') return '⚠️'.$statusKey;
        if($statusKey == 'SENSOR BROKEN') return '❌'.$statusKey;
        return $statusKey;
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

    public function setPorts($ports)
    {
        if(is_array($ports) && count($ports) > 0) {
            $alarms = groupNewosaseRtuPort($ports);
            $this->setData('alarms', $alarms);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setWitel($witel)
    {
        $this->setData('witel', $witel);
        $this->params->text = $this->getText()->get();
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