<?php

use Goat1000\SVGGraph\SVGGraph;
use MuhammadSabri1306\MyBotLogger\Entities\HttpClientLogger;
use MuhammadSabri1306\MyBotLogger\Entities\ErrorLogger;
use App\Libraries\HttpClient\Exceptions\ClientException;
use App\Libraries\HttpClient\Exceptions\DataNotFoundException;
use App\ApiRequest\NewosaseApiV2;
use App\Config\AppConfig;
use App\Helper\Helper;

if(AppConfig::$MODE == 'production') {
    AppConfig::addErrorExclusions('notice', function($err) {
        if($err->getMessage() != 'NOTICE:iconv(): Detected an illegal character in input string') {
            return false;
        }

        $currPath = \App\Helper\Helper::appPath('Controller/Bot/PortController/get-port-chart.php');
        $errTrace = $err->getTrace();
        foreach($errTrace as $trace) {
            if(isset($trace['file']) && $trace['file'] == $currPath) {
                return true;
            }
        }
        return false;
    });
}

$portChart = null;

$currHourDateTime = new \DateTime();
$currHourDateTime->setTime($currHourDateTime->format('H'), 0, 0);
$fileNamePrefix = "checkport_chart_port_$portId";
$fileName = $fileNamePrefix . '_' . $currHourDateTime->getTimestamp();

$svgFileName = "$fileName.svg";
$svgFilePath = Helper::publicPath("charts/$svgFileName");

$pngFileName = "$fileName.png";
$pngFilePath = Helper::publicPath("charts/$pngFileName");

// Check if chart on the current hour is exists
if(file_exists($pngFilePath)) {
    $portChart = Helper::env('PUBLIC_URL', '') . "/public/charts/$pngFileName";
    return $portChart;
}

// Delete previous chart files
try {
    $dirName = Helper::publicPath('charts');
    $dir = opendir($dirName);
    while(( $searchedFileName = readdir($dir) ) !== false) {
        if($searchedFileName == '.' || $searchedFileName == '..') {
            continue;
        }
        if(strpos($searchedFileName, $fileNamePrefix) === 0) {
            $matchedFilePath = $dirName . DIRECTORY_SEPARATOR . $searchedFileName;
            if(file_exists($matchedFilePath)) {
                unlink($matchedFilePath);
            }
        }
    }
    closedir($dir);
} catch(\Throwable $err) {
    static::logError( new ErrorLogger($err) );
}

// Generate new chart
$currDateTime = new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
$currDateTimeStr = $currDateTime->format('Y-m-d H:i:s');
$currDateTime->setTime(0, 0, 0);
$currTimestamp = $currDateTime->getTimestamp();
$endTime = $currTimestamp * 1000;
$startTime = ($currTimestamp - (48 * 3600)) * 1000;

$newosasePoolingUrlPath = "/dashboard-service/operation/chart/pooling/$portId";
$newosasePoolingUrlParams = [
    'start' => $startTime,
    'end' => $endTime,
    'timeframe' => 'hour',
    'is_formula' => 0
];

$newosaseApi = new NewosaseApiV2();
$newosaseApi->setupAuth();
$newosaseApi->request['query'] = $newosasePoolingUrlParams;

$poolingData = [];
try {

    $osaseData = $newosaseApi->sendRequest('GET', $newosasePoolingUrlPath);
    $poolingData = $osaseData->find('result', NewosaseApiV2::EXPECT_ARRAY_NOT_EMPTY);

} catch(ClientException $err) {
    static::logError( new HttpClientLogger($err) );
    return null;
} catch(DataNotFoundException $err) {
    // static::logError( new ErrorLogger($err) );
    return null;
}

$newosaseTresholdUrlPath = "/dashboard-service/operation/port/treshold/$portId";
$newosaseApi->request['query'] = [];
$tresholdTop = null;
$tresholdBottom = null;
try {

    $osaseData = $newosaseApi->sendRequest('GET', $newosaseTresholdUrlPath);
    $rules = $osaseData->find('result.rules', NewosaseApiV2::EXPECT_ARRAY_NOT_EMPTY);

    $tTresholds = [];
    $bTresholds = [];
    foreach($rules as $rulesItem) {

        if(preg_match('/val\s*<\s*(\d+)/', $rulesItem->rule, $matches)) {
            $tresholdVal = ( $matches[1] === (int) $matches[1] ) ? (int) $matches[1] : (double) $matches[1];
            array_push($tTresholds, $tresholdVal);
        }

        if(preg_match('/val\s*>\s*(\d+)/', $rulesItem->rule, $matches)) {
            $tresholdVal = ( $matches[1] === (int) $matches[1] ) ? (int) $matches[1] : (double) $matches[1];
            array_push($bTresholds, $tresholdVal);
        }

    }
    
    if(!empty($tTresholds)) $tresholdTop = max($tTresholds);
    if(!empty($bTresholds)) $tresholdBottom = min($bTresholds);

} catch(ClientException $err) {
    static::logError( new HttpClientLogger($err) );
    return null;
} catch(DataNotFoundException $err) {}

$dataMax = [ 'title' => 'Maximum', 'color' => '#ff4560', 'dash' => null, 'data' => [] ];
$dataAvg = [ 'title' => 'Average', 'color' => '#11d190', 'dash' => null, 'data' => [] ];
$dataMin = [ 'title' => 'Minimum', 'color' => '#775dd0', 'dash' => null, 'data' => [] ];
$dataLimitT = [ 'title' => 'Batas Atas', 'color' => '#008ffb', 'dash' => '5,3', 'data' => [] ];
$dataLimitB = [ 'title' => 'Batas Bawah', 'color' => '#775dd0', 'dash' => '5,3', 'data' => [] ];

$isDataEmpty = true;
foreach($poolingData as $item) {

    $itemDate = date('Y-m-d\TH:i', $item->timestamps / 1000);

    if(isset($item->value_max)) {
        $dataMax['data'][$itemDate] = $item->value_max;
        $isDataEmpty = false;
    }

    if(isset($item->value_avg)) {
        $dataAvg['data'][$itemDate] = $item->value_avg;
        $isDataEmpty = false;
    }

    if(isset($item->value_min)) {
        $dataMin['data'][$itemDate] = $item->value_min;
        $isDataEmpty = false;
    }

}

if($isDataEmpty) {
    return $portChart;
}

$chartStartDate = date('Y-m-d\TH:i', $startTime / 1000);
$chartEndDate = date('Y-m-d\TH:i', $endTime / 1000);

if($tresholdTop) {
    $dataLimitT['data'][$chartStartDate] = $tresholdTop;
    $dataLimitT['data'][$chartEndDate] = $tresholdTop;
}

if($tresholdBottom) {
    $dataLimitB['data'][$chartStartDate] = $tresholdBottom;
    $dataLimitB['data'][$chartEndDate] = $tresholdBottom;
}

$chartData = [];

$isApplyLimit = count($dataLimitT['data']) > 0 && count($dataLimitB['data']) > 0;
if($isApplyLimit) {
    array_push($chartData, $dataLimitT, $dataLimitB);
}

if(count($dataMax['data']) > 0) array_push($chartData, $dataMax);
if(count($dataAvg['data']) > 0) array_push($chartData, $dataAvg);
if(count($dataMin['data']) > 0) array_push($chartData, $dataMin);

list($minValue, $maxValue) = array_reduce($chartData, function($result, $item) {
        
    if(!isset($item['data']) || count($item['data']) < 1) return $result;

    $minItem = min(array_values($item['data']));
    if( !$result[0] || ($result[0] && $result[0] > $minItem) ) {
        $result[0] = $minItem;
    }

    $maxItem = max(array_values($item['data']));
    if( !$result[1] || ($result[1] && $result[1] < $maxItem) ) {
        $result[1] = $maxItem;
    }

    return $result;

}, [null, null]);

$vAxisBase = 20;
$vAxisMin = floor($minValue / $vAxisBase) * $vAxisBase;
$vAxisMax = ( ceil($maxValue) % $vAxisBase === 0 ) ? ceil($maxValue) : round(( $maxValue + $vAxisBase / 2 ) / $vAxisBase) * $vAxisBase;

$imgWidth = 300;
$imgHeight = 200;

$settings = [
    'use_iconv' => false,

    'auto_fit' => true,
    'back_colour' => '#fff',
    'back_stroke_width' => 0,
    'back_stroke_colour' => '#eee',

    'graph_title' => 'Data diambil pada ' . $currDateTimeStr,
    'graph_title_font_size' => 5,

    'axis_colour' => '#f4f4f4',
    'axis_text_colour' => '#373d3f',
    'axis_overlap' => 2,
    'grid_colour' => '#f4f4f4',
    'axis_font' => 'Arial',
    'axis_font_size' => 6,
    'axis_text_angle_h' => -45,
    'axis_min_v' => $vAxisMin,
    'axis_max_v' => $vAxisMax,
    'axis_font_size' => 5,

    'fill_under' => array_map(fn($item) => true, $chartData),
    'label_colour' => '#373d3f',

    'pad_right' => 10,
    'pad_left' => 10,
    'pad_top' => 10,
    'pad_bottom' => 50,

    'marker_type' => array_map(fn($item) => 'circle', $chartData),
    'marker_size' => 0,
    'marker_colour' => array_map(fn($item) => $item['color'], $chartData),
    'show_grid_h' => true,
    'show_grid_v' => false,

    'line_curve' => 0.4,
    'line_stroke_width' => 2,
    'line_dash' => array_map(fn($item) => $item['dash'], $chartData),

    'datetime_keys' => true,
    'datetime_text_format' => 'M-d H:i',

    'legend_columns' => 3,
    'legend_entry_height' => 10,
    'legend_text_side' => 'left',
    'legend_position' => 'outer bottom 40 -10',
    'legend_font_size' => 5,
    'legend_stroke_width' => 0,
    'legend_shadow_opacity' => 0,
    'legend_draggable' => false,
    'legend_back_colour' => '#fff',
    'legend_entries' => array_map(fn($item) => $item['title'], $chartData)
];

$graph = new SVGGraph($imgWidth, $imgHeight, $settings);
$graph->colours(array_map(function($item) {
    $color = $item['color'];
    return [ "$color:0", "$color:0" ];
}, $chartData));

$graph->values(array_map(fn($item) => $item['data'], $chartData));
$svgImg = $graph->fetch('MultiLineGraph');

$pngGeneratorCmd = "node /var/www/html/newopnimus/app/CLI/svg-to-png charts/$svgFileName";
try {

    if(file_put_contents($svgFilePath, $svgImg) === false) {
        throw new \Exception('Fail to write svg file');
    }

    $output = null;
    if(exec($pngGeneratorCmd, $output) === false) {
        throw new \Exception('Fail to write png file');
    }

    $portChart = Helper::publicPath("charts/$pngFileName");
    return $portChart;

} catch(\Throwable $err) {

    $logger = new ErrorLogger($err);
    $logger->setParams([
        'png_path' => $pngFilePath,
        'svg_path' => $svgFilePath,
        'png_generator' => $pngGeneratorCmd,
        'apis' => [
            [ 'path' => $newosasePoolingUrlPath, 'params' => $newosasePoolingUrlParams ],
            [ 'path' => $newosaseTresholdUrlPath ],
        ]
    ]);

    static::logError($logger);

}

return $portChart;