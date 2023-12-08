<?php
namespace App\Helper;

class DateHelper
{
    public static function max(...$dateList) {
        $times = array_map(function($date) {
            $dateTime = new \DateTime($date);
            return $dateTime->getTimestamp();
        }, $dateList);

        return date('Y-m-d H:i:s', max($times));
    }
}