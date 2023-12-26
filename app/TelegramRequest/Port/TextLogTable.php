<?php
namespace App\TelegramRequest\Port;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;
use App\Core\TelegramRequest\TextList;
use App\Core\TelegramRequest\PortFormat;

class TextLogTable extends TelegramRequest
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
        $isLevelRtu = $this->getData('level', 'rtu') == 'rtu';
        $rtuSname = $this->getData('rtu_sname', null);
        $locName = $this->getData('loc_name', null);
        $witelName = $this->getData('witel_name', null);
        $alarmPorts = $this->getData('alarms', []);
        $currDate = date('Y-m-d H:i');

        $text = TelegramText::create('Log Alarm Harian');
        if($rtuSname) $text->addText(" $rtuSname");
        if($locName) $text->addText(" Lokasi STO $locName");
        if($witelName) $text->addText(" $witelName");
        $text->addText(':')->newLine(2);

        if(count($alarmPorts) < 1) {
            return $text->addItalic('Belum ada Alarm hari ini.');
        }

        $text->addText("Data diambil pukul $currDate WIB")
            ->newLine()
            ->startCode();

        $getFixedChar = function(string $textStr, int $minChar): string {
            $textStrLength = strlen($textStr);
            if($textStrLength >= $minChar) return $textStr;
            return TelegramText::create($textStr)->addSpace($minChar - $textStrLength)->get();
        };

        $text->addText( $getFixedChar('No', 2) )
            ->addText(' | ')->addText( $getFixedChar('Waktu', 16) );
        if(!$isLevelRtu) $text->addText(' | ')->addText( $getFixedChar('RTU', 14) );
        $text->addText(' | ')->addText('Alarm');
            
        foreach($alarmPorts as $index => $alarm) {
            $no = $index + 1;
            $alarmDate = \DateTime::createFromFormat('Y-m-d H:i:s', $alarm['opened_at'])->format('Y-m-d H:i');
            $alarmPortSeverity = $alarm['port_severity'];
            $alarmPortName = $alarm['port_name'];
            
            if($this->isOffPort($alarm['port_unit'])) $alarmPortValue = $this->formatBinerPortValue($alarm['port_value'], 'OFF', 'ON');
            if($this->isBinerPort($alarm['port_unit'])) $alarmPortValue = $this->formatBinerPortValue($alarm['port_value'], 'ON', 'OFF');
            else $alarmPortValue = $this->formatPortValue($alarm['port_value'], $alarm['port_unit']);

            $text->newLine()->addText( $getFixedChar("$no", 2) )
                ->addText(' | ')->addText( $getFixedChar($alarmDate, 16) );
            if(!$isLevelRtu) $text->addText(' | ')->addText( $getFixedChar($alarm['rtu_sname'], 16) );
            $text->addText(' | ')->addText("$alarmPortSeverity $alarmPortName $alarmPortValue");

        }

        return $text->endCode();
    }

    public function setLevel($level)
    {
        if(is_string($level)) {
            $this->setData('level', $level);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setRtuSname($rtuSname)
    {
        if(is_string($rtuSname)) {
            $this->setData('rtu_sname', $rtuSname);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setLocationName($locName)
    {
        if(is_string($locName)) {
            $this->setData('loc_name', $locName);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setWitelName($witelName)
    {
        if(is_string($witelName)) {
            $this->setData('witel_name', $witelName);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setAlarmPorts($alarms)
    {
        if(is_array($alarms)) {
            $this->setData('alarms', $alarms);
            $this->params->text = $this->getText()->get();
        }
    }

    public function send(): ServerResponse
    {
        $text = $this->params->text;
        $messageTextList = $this->splitText($text, 50);

        if(count($messageTextList) <= 50) {
            return Request::sendMessage($this->params->build());
        }
        return $this->sendList($messageTextList);
    }
}