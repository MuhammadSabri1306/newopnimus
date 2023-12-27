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

    public static function dateDiff(string $startDatetime, string $endDatetime, string $format = '%dd %hh %im %ss') {
        $start = new \DateTime($startDatetime);
        $end = new \DateTime($endDatetime);
        $interval = $start->diff($end);
    
        $formattedInterval = $interval->format($format);
        return $formattedInterval;
    }
}