<?php
namespace App\BuiltMessageText;

use App\Core\TelegramText;
use App\Controller\BotController;
useHelper('date');
useHelper('number');

class PortText
{
    public static function getPortInKeyboardText()
    {
        return TelegramText::create('Silahkan pilih')
            ->startBold()->addText(' Port ')->endBold()
            ->addText('yang ingin anda cek.');
    }

    public static function getRtuInKeyboardText()
    {
        return TelegramText::create('Silahkan pilih')
            ->startBold()->addText(' RTU ')->endBold()
            ->addText('untuk Port yang ingin anda cek.');
    }

    public static function getLocationInKeyboardText()
    {
        return TelegramText::create('Silahkan pilih')
            ->startBold()->addText(' Lokasi ')->endBold()
            ->addText('untuk Port yang ingin anda cek.');
    }

    public static function getWitelInKeyboardText()
    {
        return TelegramText::create('Silahkan pilih')
            ->startBold()->addText(' Witel ')->endBold()
            ->addText('untuk Port yang ingin anda cek.');
    }

    public static function getRegionalInKeyboardText()
    {
        return TelegramText::create('Silahkan pilih')
            ->startBold()->addText(' Regional ')->endBold()
            ->addText('untuk Port yang ingin anda cek.');
    }

    public static function getAllPortsText($ports)
    {
        $textList = [];
        $firstPort = $ports[0];
        $groupedPorts = array_reduce($ports, function($group, $port) {

            $key = $port->port_name ?? "PORT $port->no_port";
            if(!isset($group[$key])) $group[$key] = [];
            array_push($group[$key], $port);
            return $group;

        }, []);

        $text = TelegramText::create()
            ->addText('🧾Berikut daftar Port yang terdapat pada ')
            ->startBold()->addText("$firstPort->rtu_sname ($firstPort->location) $firstPort->witel $firstPort->regional")->endBold()
            ->addText(':')->newLine(2)
            ->startItalic()->addText('Data Diambil pada: '.date('Y-m-d H:i:s').' WIB')->endItalic();
        
        foreach($groupedPorts as $portName => $portList) {
            $text->newLine()
                ->startBold()->addText($portName)->endBold();
            
            foreach($portList as $port) {
                $portIcon = PortText::buildSeverityIcon($port->severity->name);
                $portValue = PortText::buildPortValue($port);
                $portStatus = $port->severity->name;

                $text->newLine()
                    ->startCode()
                    ->addSpace()
                    ->addText("$portIcon ($port->no_port) $port->description ($portValue) status $portStatus")
                    ->endCode();
            }
            $text->newLine();
        }

        return $text;
    }

    public static function getDetailPortText($port)
    {
        $portUnit = htmlspecialchars($port->units);
        $portStatus = $port->severity->name;

        $portUpdatedAt = date('Y-m-d H:i:s', floor($port->updated_at / 1000));
        $currDate = date('Y-m-d H:i:s');
        $duration = dateDiff($portUpdatedAt, $currDate);

        return TelegramText::create()
            ->addText('🧾Berikut detail Port ')
            ->startBold()->addText("$port->no_port")->endBold()
            ->addText(' yang terdapat pada ')
            ->startBold()->addText("$port->rtu_sname ($port->location) $port->witel $port->regional")->endBold()
            ->addText(':')->newLine(2)
            ->startItalic()->addText('Data Diambil pada: '.date('Y-m-d H:i:s').' WIB')->endItalic()->newLine(2)
            ->startCode()
            ->addText("Nama Port : $port->port_name")->newLine()
            ->addText('Tipe Port : -')->newLine()
            ->addText('Jenis Port: -')->newLine()
            ->addText("Satuan    : $portUnit")->newLine()
            ->addText("Value     : $port->value")->newLine()
            ->addText("Status    : $portStatus")->newLine()
            ->addText("Durasi Up : $duration")->newLine()
            ->endCode()->newLine(2)
            ->addText('#OPNIMUS #CEKPORT #PORT\_DETAIL');
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

    private static function buildSeverityIcon(string $severityName)
    {
        $statusKey = strtoupper($severityName);
        if($statusKey == 'NORMAL') return '✅';
        if($statusKey == 'OFF') return '‼️';
        if($statusKey == 'CRITICAL') return '❗️';
        if($statusKey == 'WARNING') return '⚠️';
        if($statusKey == 'SENSOR BROKEN') return '❌';
        return '';
    }
}