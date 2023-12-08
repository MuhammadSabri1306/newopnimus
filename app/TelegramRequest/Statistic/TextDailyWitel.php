<?php
namespace App\TelegramRequest\Statistic;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;
use App\Helper\ArrayHelper;
use App\Helper\DateHelper;
use App\Helper\NumberHelper;

class TextDailyWitel extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $witel = $this->getData('witel', null);
        $alarmPorts = $this->getData('alarm_ports', null);
        $alarmRtus = $this->getData('alarm_rtus', []);
        $openAlarmCount = $this->getData('open_alarm_count', 0);
        $closeAlarmCount = $this->getData('close_alarm_count', 0);

        if(!$witel || !is_array($alarmPorts)) {
            return TelegramText::create();
        }

        $witelName = $witel['witel_name'];
        $currDate = date('Y-m-d H:i:s');
        $alarmPortsCount = count($alarmPorts);
        $alarmRtusCount = count($alarmRtus);

        $text = TelegramText::create()
            ->addText("Statistik HARIAN Anomali Perangkat $witelName pada $currDate.")->newLine(2)
            ->addBold("〽️STATISTIK $witelName:")->newLine(2)
            ->addItalic("- TOTAL ALARM RTU  : $alarmRtusCount")->newLine()
            ->addItalic("- TOTAL ALARM PORT : $alarmPortsCount")->newLine()
            ->addSpace(7)->addItalic("➥ alarm off: $closeAlarmCount")->newLine()
            ->addSpace(7)->addItalic("➥ alarm on: $openAlarmCount");

        if($alarmPortsCount < 1) {
            return $text;
        }
            
        $text->newLine(2)
            ->addText('🌟')->addBold('DETAIL RTU DOWN HARI INI')->newLine(2)
            ->addSpace()->addText("🌇$witelName")->startCode();
        
        foreach($alarmRtus as $index => $rtu) {

            $rtuSname = $rtu['rtu_sname'];
            $locName = $rtu['location_name'];
            $openCount = $rtu['count'].'x';
            $lastOpenedAt = $rtu['last_opened_at'];

            if($index > 0) $text->newLine();
            $text->addSpace(2)->addText("- $rtuSname $locName: DOWN $openCount")->newLine()
                ->addSpace(4)->addText("(Last down $lastOpenedAt)");
                
        }

        $text->endCode()->newLine(2)
            ->addText('🎚')->addBold('TOP 10 ALARM PORT HARI INI:')->startCode();

        $maxAlarmPortsIndex = min([ count($alarmPorts), 10 ]);
        for($i=0; $i<$maxAlarmPortsIndex; $i++) {

            $no = $i + 1;
            $portSeverity = ucfirst($alarmPorts[$i]['port_severity']);
            $rtuSname = $alarmPorts[$i]['rtu_sname'];
            $portName = $alarmPorts[$i]['port_name'];
            $locName = $alarmPorts[$i]['location_name'];
            $witelName = $alarmPorts[$i]['witel_name'];
            $lastPortValue = $this->formatPortValue($alarmPorts[$i]['last_port_value'], $alarmPorts[$i]['port_unit']);
            $lastOpenedAt = $alarmPorts[$i]['last_opened_at'];
            $openCount = $alarmPorts[$i]['count'].'x';

            if($i > 0) $text->newLine();
            $text->addSpace('2')
                ->addText("$no.($portSeverity) $rtuSname $portName $locName $witelName ($lastPortValue - $lastOpenedAt): Alarm $openCount");
        }

        return $text->endCode();
    }

    protected function formatPortValue($portValue, $portUnit)
    {
        $portUnitKey = strtoupper($portUnit);

        if(in_array($portUnitKey, ['OFF', 'ON/OFF'])) {
            return boolval($portValue) ? 'OFF' : 'ON';
        }

        if($portUnitKey == 'OPEN/CLOSE') {
            return boolval($portValue) ? 'OPEN' : 'CLOSE';
        }
        
        if(is_null($portValue)) {
            return 'null';
        }

        $value = NumberHelper::toNumber($portValue);
        $unit = $portUnit;

        $unit = utf8_encode($unit);
        $value = utf8_encode($value);
        
        if(in_array($portUnitKey, ['#', '%', '%RH', '-'])) {
            return $value.$unit;
        }

        return "$value $unit";
    }

    public function setWitel($witel)
    {
        $this->setData('witel', $witel);
        $this->params->text = $this->getText()->get();
    }

    public function setAlarms(array $alarms)
    {
        $rtus = [];
        $ports = [];
        $openAlarms = [];
        $closeAlarms = [];

        foreach($alarms as $alarm) {

            $rtuIndex = ArrayHelper::findIndex($rtus, fn($rtuItem) => $rtuItem['rtu_sname'] == $alarm['rtu_sname']);
            if($rtuIndex < 0) {
                $rtu = ArrayHelper::duplicateByKeysRegex($alarm, '/^(rtu_|location_|datel_|witel_|regional_)/');
                array_push($rtus, [
                    ...$rtu,
                    'count' => 1,
                    'last_opened_at' => $alarm['opened_at']
                ]);
            } else {
                $rtus[$rtuIndex]['count']++;
                $rtus[$rtuIndex]['last_opened_at'] = DateHelper::max($rtus[$rtuIndex]['last_opened_at'], $alarm['opened_at']);
            }

            $portKey = $alarm['rtu_sname'].'.'.$alarm['type'].'.'.$alarm['port_no'].'.'.$alarm['port_unit'];
            $portIndex = ArrayHelper::findIndex($ports, fn($item) => $item['key'] == $portKey);
            if($portIndex < 0) {
                array_push($ports, [
                    ...$alarm,
                    'key' => $portKey,
                    'count' => 1,
                    'last_port_value' => $alarm['port_value'],
                    'last_opened_at' => $alarm['opened_at']
                ]);
            } else {
                $ports[$portIndex]['count']++;
                $ports[$portIndex]['last_opened_at'] = DateHelper::max($ports[$portIndex]['last_opened_at'], $alarm['opened_at']);
                if($alarm['opened_at'] == $ports[$portIndex]['last_opened_at']) {
                    $ports[$portIndex]['last_port_value'] = $alarm['port_value'];
                }
            }

            if(!in_array($portKey, $openAlarms)) {
                array_push($openAlarms, $portKey);
            }

            if($alarm['is_closed'] == 1 && !in_array($portKey, $closeAlarms)) {
                array_push($closeAlarms, $portKey);
            }

        }
        
        $rtus = ArrayHelper::sort($rtus, fn($a, $b) => [ $b['count'], $a['count'] ]);
        $ports = ArrayHelper::sort($ports, fn($a, $b) => [ $b['count'], $a['count'] ]);
        $openAlarms = array_diff($openAlarms, $closeAlarms);

        $this->setData('alarm_rtus', $rtus);
        $this->setData('alarm_ports', $ports);
        $this->setData('open_alarm_count', count($openAlarms));
        $this->setData('close_alarm_count', count($closeAlarms));

        $this->params->text = $this->getText()->get();
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}