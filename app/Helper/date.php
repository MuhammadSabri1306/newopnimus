<?php

function dateDiff(string $startDatetime, string $endDatetime, string $format = '%dd %hh %im %ss') {
    $start = new DateTime($startDatetime);
    $end = new DateTime($endDatetime);
    $interval = $start->diff($end);

    $formattedInterval = $interval->format($format);
    return $formattedInterval;
}

function timeToDateString($time) {
    $seconds = floor($time / 1000);
    return date('Y-m-d H:i:s', $seconds);
}

function dateStringToTime(string $date) {
    $time = strtotime($date);
    return $time * 1000;
}