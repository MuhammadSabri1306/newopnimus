<?php

function dateDiff(string $startDatetime, string $endDatetime, string $format = '%dd %hh %im %ss') {
    $start = new DateTime($startDatetime);
    $end = new DateTime($endDatetime);
    $interval = $start->diff($end);

    $formattedInterval = $interval->format($format);
    return $formattedInterval;
}