<?php
require __DIR__.'/../app/bootstrap.php';

use App\Helper\ArrayHelper;

$testNumeric = [2, 5, 4, 1];
$testNumericAsc = ArrayHelper::sort($testNumeric, fn($a, $b) => [$a, $b]);
$testNumericDesc = ArrayHelper::sort($testNumeric, fn($a, $b) => [$b, $a]);

$testString = ['dua', 'lima', 'empat', 'satu',];
$testStringAsc = ArrayHelper::sortStr($testString, fn($a, $b) => [$a, $b]);
$testStringDesc = ArrayHelper::sortStr($testString, fn($a, $b) => [$b, $a]);

$testNumeric2 = [
    [ 'count' => 2, 'text' => 'dua' ],
    [ 'count' => 5, 'text' => 'lima' ],
    [ 'count' => 4, 'text' => 'empat' ],
    [ 'count' => 1, 'text' => 'satu' ]
];
$testNumeric2Asc = ArrayHelper::sort($testNumeric2, fn($a, $b) => [$a['count'], $b['count']]);
$testNumeric2Desc = ArrayHelper::sort($testNumeric2, fn($a, $b) => [$b['count'], $a['count']]);

$test = [
    'numeric_1' => [
        'TEST' => $testNumeric,
        'ASC' => $testNumericAsc,
        'DESC' => $testNumericDesc,
    ],
    'numeric_2' => [
        'TEST' => $testNumeric2,
        'ASC' => $testNumeric2Asc,
        'DESC' => $testNumeric2Desc,
    ],
    'string' => [
        'TEST' => $testString,
        'ASC' => $testStringAsc,
        'DESC' => $testStringDesc,
    ],
];

dd_json($test);