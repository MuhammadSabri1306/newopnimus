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
        $regional = Regional::find($regionalId);
        $alarms = groupPortData($ports, ['witel', 'rtu']);
        $currDateTime = date('Y-m-d H:i:s');

        $text = TelegramText::create()
            ->addText('Status alarm OSASE di ')
            ->startBold()->addText($regional['name'])->endBold()
            ->addText(" pada $currDateTime WIB adalah:")->newLine();

        foreach($alarms as $witel) {
            $text->newLine()->addText('⛺️')->startBold()->addText($witel['witel_name'])->endBold()->addText(' :');
            foreach($witel['rtus'] as $rtu) {

                $text->newLine()
                    ->addSpace(2)
                    ->addText('⛽️'.$rtu['rtu_code'].' ('.$rtu['location']['location_name'].') :');
                foreach($rtu['ports'] as $port) {

                    $portStatusTitle = AlarmText::buildPortStatusTitle($port);
                    $portName = $port['port_name'];
                    $portValue = AlarmText::buildPortValue($port);
                    $duration = dateDiff($port['start_at'], $currDateTime);

                    $text->newLine()
                        ->startCode()
                        ->addSpace(4)
                        ->addText("$portStatusTitle: $portName ($portValue) selama $duration")
                        ->endCode();
                }
            }
        }

        return $text;
    }

    public static function witelAlarmText($witelId, array $ports)
    {
        $witel = Witel::find($witelId);
        $alarms = groupPortData($ports, ['rtu']);
        $currDateTime = date('Y-m-d H:i:s');

        $text = TelegramText::create()
            ->addText('Status alarm OSASE di ')
            ->startBold()->addText($witel['witel_name'])->endBold()
            ->addText(" pada $currDateTime WIB adalah:")->newLine();

        foreach($alarms as $rtu) {

            $text->newLine()
                ->addText('⛽️'.$rtu['rtu_code'].' ('.$rtu['location']['location_name'].') :');
            foreach($rtu['ports'] as $port) {

                $portStatusTitle = AlarmText::buildPortStatusTitle($port);
                $portName = $port['port_name'];
                $portValue = AlarmText::buildPortValue($port);
                $duration = dateDiff($port['start_at'], $currDateTime);

                $text->newLine()
                    ->startCode()
                    ->addSpace(2)
                    ->addText("$portStatusTitle: $portName ($portValue) selama $duration")
                    ->endCode();
            }
        }

        return $text;
    }

    private static function buildPortStatusTitle(array $port)
    {
        if($port['port_name'] == 'Status PLN') return '⚡️ PLN OFF';
        if($port['port_name'] == 'Status DEG') return '🔆 GENSET ON';

        $statusKey = strtoupper($port['port_status']);
        if($statusKey == 'OFF') return '‼️'.$statusKey;
        if($statusKey == 'CRITICAL') return '❗️'.$statusKey;
        if($statusKey == 'WARNING') return '⚠️'.$statusKey;
        if($statusKey == 'SENSOR BROKEN') return '❌'.$statusKey;
        return $statusKey;
    }

    private static function buildPortValue(array $port)
    {
        $portUnitKey = strtoupper($port['unit']);

        if(in_array($portUnitKey, ['OFF', 'ON/OFF'])) {
            return boolval($port['value']) ? 'OFF' : 'ON';
        }

        if($portUnitKey == 'OPEN/CLOSE') {
            return boolval($port['value']) ? 'OPEN' : 'CLOSE';
        }

        $value = convertToNumber($port['value']);
        $unit = htmlentities($port['unit']);

        if(in_array($portUnitKey, ['#', '%', '%RH', '-'])) {
            return $value.$unit;
        }

        return "$value $unit";
    }
}