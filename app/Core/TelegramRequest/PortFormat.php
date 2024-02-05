<?php
namespace App\Core\TelegramRequest;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramText;
use App\Helper\NumberHelper;

trait PortFormat
{
    protected function formatPortValue($portValue, $portUnit = '-')
    {
        $portUnitKey = strtoupper($portUnit);
        
        if($portUnit == '-') {
            return $portValue;
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

    protected function isOffPort($portUnit)
    {
        return strtoupper($portUnit) == 'OFF';
    }

    protected function isBinerPort($portUnit)
    {
        $binerUnits = [ 'ON/OFF', 'OPEN/CLOSE' ];
        return in_array(strtoupper($portUnit), $binerUnits);
    }

    protected function formatBinerPortValue($portValue, $trueValue, $falseValue)
    {
        return boolval($portValue) ? $trueValue : $falseValue;
    }

    protected function getAlarmIcon($portNo, $portName, $portSeverity)
    {
        if($portNo == 'D-02') return '⚡️';
        if($portNo == 'D-01') return '🔆';

        $portName = strtolower($portName);
        if(strpos($portName, 'temperature') !== false) {
            return "🌡️";
        }

        $statusKey = strtolower($portSeverity);
        if($statusKey == 'normal') return '✅';
        if($statusKey == 'off') return '‼️';
        if($statusKey == 'critical') return '❗️';
        if($statusKey == 'warning') return '⚠️';
        if($statusKey == 'sensor broken') return '❌';
        return '';
    }

    protected function toDefaultPortValueFormat($portValue, $portUnit, $portIdentifier)
    {
        if(is_null($portValue)) return 'null';

        if($this->isOffPort($portUnit)) {
            return $this->formatBinerPortValue($portValue, 'OFF', 'ON');
        }

        if($this->isBinerPort($portUnit)) {
            if($portIdentifier == 'ST_PLN') {
                return $this->formatBinerPortValue($portValue, 'OFF', 'ON');
            }
            return $this->formatBinerPortValue($portValue, 'ON', 'OFF');
        }

        $portValue = NumberHelper::toNumber($portValue);
        $portValue = NumberHelper::round($portValue, 2);
        if($portUnit == '-') return $portValue;

        $portUnitUtf8 = utf8_encode($portUnit);
        if(strpos($portUnitUtf8, '°') !== false) {
            $portUnit = $portUnitUtf8;
        }

        // $unit = utf8_encode($unit);
        // $value = utf8_encode($value);
        // $portUnitKey = strtoupper($portUnit);
        // if(in_array($portUnitKey, ['#', '%', '%RH'])) {
        //     return $value.$unit;
        // }

        if(strlen($portUnit) < 2) {
            return $portValue.$portUnit;
        }
        return "$portValue $portUnit";
    }
}