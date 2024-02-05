<?php
namespace App\Helper;

class NumberHelper
{
    public static function toNumber($value) {
        if($value === null || strtolower($value) === 'null') {
            return 0;
        }
        
        if(is_numeric($value) && strpos($value, '.') !== false) {
            return (double)$value;
        }
        
        if(is_numeric($value) && strpos($value, '.') === false) {
            return (int)$value;
        }
    
        return null;
    }

    public static function round($numb, $precision = 0) {
        if($numb === null) return 0;

        $numbPow = pow(10, $precision);
        $result = floor($numb * $numbPow) / $numbPow;
        $lastDecimal = ($numb - $result) * $numbPow;
        if($lastDecimal < 0.5) return $result;

        $result = (ceil($result * $numbPow) + 1) / $numbPow;
        return $result;
    }
}