<?php
namespace App\BuiltMessageText;

use App\Core\TelegramText;
use App\Model\Regional;
use App\Model\Witel;

useHelper('area');
useHelper('number');
useHelper('date');

class AlarmText
{
    public static function regionalAlarmText($regionalId, array $ports)
    {
        $textList = [];
        $regional = Regional::find($regionalId);
        $alarms = groupNewosaseWitelPort($ports);
        $currDateTime = date('Y-m-d H:i:s');

        $text = TelegramText::create()
            ->addText('Status alarm OSASE di ')
            ->startBold()->addText($regional['name'])->endBold()
            ->addText(" pada $currDateTime WIB adalah:")->newLine();
        array_push($textList, $text);

        foreach($alarms as $witel) {
            foreach($witel->rtus as $rtu) {

                $text = TelegramText::create()
                    ->addText('⛺️')->startBold()->addText($rtu->witel)->endBold()->addSpace(2)
                    ->addText("⛽️$rtu->rtu_sname ($rtu->location) :");

                $rtuIndex = 0;
                foreach($rtu->ports as $port) {

                    $portStatusTitle = AlarmText::buildPortStatusTitle($port);
                    $portName = $port->port_name ?? $port->no_port;
                    $portValue = AlarmText::buildPortValue($port);
                    $duration = dateDiff(timeToDateString($port->updated_at), $currDateTime);

                    $text->newLine(2)
                        ->startCode()
                        ->addSpace(3)
                        ->addText("$portStatusTitle: $portName ($portValue) selama $duration")
                        ->endCode();

                    $rtuIndex++;
                    if($rtuIndex === 10) {
                        array_push($textList, $text);
                        $text = TelegramText::create();
                    }

                }

                array_push($textList, $text);
            }
        }

        return $textList;
    }

    public static function regionalAlarmText1($regionalId, array $ports)
    {
        $regional = Regional::find($regionalId);
        $alarms = groupNewosaseWitelPort($ports);
        $currDateTime = date('Y-m-d H:i:s');

        $text = TelegramText::create()
            ->addText('Status alarm OSASE di ')
            ->startBold()->addText($regional['name'])->endBold()
            ->addText(" pada $currDateTime WIB adalah:");

        foreach($alarms as $witelName => $witel) {
            $text->newLine(2)
                ->startBold()->addText("⛺️$witelName")->endBold();

            foreach($witel->rtus as $rtu) {
                $text->newLine(2)
                    ->addSpace(2)->addText("⛽️$rtu->rtu_sname ($rtu->location) :");

                foreach($rtu->ports as $port) {

                    $portStatusTitle = AlarmText::buildPortStatusTitle($port);
                    $portName = $port->port_name ?? $port->no_port;
                    $portValue = AlarmText::buildPortValue($port);
                    $duration = dateDiff(timeToDateString($port->updated_at), $currDateTime);

                    $text->newLine(2)
                        ->startCode()
                        ->addSpace(4)
                        ->addText("$portStatusTitle: $portName ($portValue) selama $duration")
                        ->endCode();
                }
                $text->newLine();
            }
            $text->newLine();
        }

        return $text;
    }

    public static function witelAlarmText($witelId, array $ports)
    {
        $textList = [];
        $witel = Witel::find($witelId);
        $alarms = groupNewosaseRtuPort($ports);
        $currDateTime = date('Y-m-d H:i:s');

        $text = TelegramText::create()
            ->addText('Status alarm OSASE di ')
            ->startBold()->addText($witel['witel_name'])->endBold()
            ->addText(" pada $currDateTime WIB adalah:")->newLine();
        array_push($textList, $text);

        $rtuIndex = 0;
        foreach($alarms as $rtu) {

            $text = TelegramText::create("⛽️$rtu->rtu_sname ($rtu->location) :");
            foreach($rtu->ports as $port) {

                $portStatusTitle = AlarmText::buildPortStatusTitle($port);
                $portName = $port->port_name ?? $port->no_port;
                $portValue = AlarmText::buildPortValue($port);
                $duration = dateDiff(timeToDateString($port->updated_at), $currDateTime);

                $text->newLine(2)
                    ->startCode()
                    ->addSpace(3)
                    ->addText("$portStatusTitle: $portName ($portValue) selama $duration")
                    ->endCode();

                $rtuIndex++;
                if($rtuIndex === 10) {
                    array_push($textList, $text);
                    $text = TelegramText::create();
                }
            }

            array_push($textList, $text);
        }

        return $textList;
    }

    public static function witelAlarmText1($witelId, array $ports)
    {
        $textList = [];
        $witel = Witel::find($witelId);
        $alarms = groupNewosaseRtuPort($ports);
        $currDateTime = date('Y-m-d H:i:s');

        $text = TelegramText::create()
            ->addText('Status alarm OSASE di ')
            ->startBold()->addText($witel['witel_name'])->endBold()
            ->addText(" pada $currDateTime WIB adalah:");

        foreach($alarms as $rtu) {
            $text->newLine(2)
                ->startBold()
                ->addText("⛽️$rtu->rtu_sname ($rtu->location) :")
                ->endBold();

            foreach($rtu->ports as $port) {
                $portStatusTitle = AlarmText::buildPortStatusTitle($port);
                $portName = $port->port_name ?? $port->no_port;
                $portValue = AlarmText::buildPortValue($port);
                $duration = dateDiff(timeToDateString($port->updated_at), $currDateTime);

                $text->newLine()
                    ->startCode()
                    ->addSpace(2)
                    ->addText("$portStatusTitle: $portName ($portValue) selama $duration")
                    ->endCode();
            }
        }

        return $text;
    }

    private static function buildPortStatusTitle($port)
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

    private static function buildPortValue($port)
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
}