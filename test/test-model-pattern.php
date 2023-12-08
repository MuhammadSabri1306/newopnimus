<?php
require __DIR__.'/../app/bootstrap.php';

use App\Model\AlarmPortStatus;

// $test1 = AlarmPortStatus::find(10826);

AlarmPortStatus::useDefaultJoinPattern();
$test = AlarmPortStatus::getWitelCurrDay(43);
dd(implode(', ', array_map(fn($item) => "'$item'", array_keys($test[0]))));
// $test = AlarmPortStatus::getWitelCurrMonth(43);
dd($test);