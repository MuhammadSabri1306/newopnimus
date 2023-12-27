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
}