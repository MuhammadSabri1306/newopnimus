<?php

use Goat1000\SVGGraph\SVGGraph;
use App\ApiRequest\NewosaseApi;
use App\Helper\Helper;
use App\Config\AppConfig;

if(AppConfig::$MODE == 'production') {
    AppConfig::addErrorExclusions('notice', function($err) {
        if($err->getMessage() != 'NOTICE:iconv(): Detected an illegal character in input string') {
            return false;
        }

        $currPath = \App\Helper\Helper::appPath('Controller/Bot/PortController/get-newosase-port-detail.php');
        $errTrace = $err->getTrace();
        foreach($errTrace as $trace) {
            if(isset($trace['file']) && $trace['file'] == $currPath) {
                return true;
            }
        }
        return false;
    });
}

$newosaseApi = new NewosaseApi();
$newosaseApi->request['query'] = $newosaseApiParams;
$fetchResponse = $newosaseApi->sendRequest('GET', '/dashboard-service/dashboard/rtu/port-sensors');

if(!$fetchResponse || !$fetchResponse->result->payload) {
    return null;
}

$ports = array_filter($fetchResponse->result->payload, fn($port) =>$port->no_port != 'many');
if(count($ports) < 1) {
    return [ 'port' => null ];
}

$data = [ 'port' => $ports[0] ];
$portId = $data['port']->id;

$currHourDateTime = new \DateTime();
$currHourDateTime->setTime($currHourDateTime->format('H'), 0, 0);
$fileName = 'checkport_chart_port_'.$portId.'_' . $currHourDateTime->getTimestamp();

$svgFileName = $fileName . '.svg';
$svgFilePath = __DIR__.'/../../../../public/charts/'.$svgFileName;

$pngFileName = $fileName . '.png';
$pngFilePath = __DIR__.'/../../../../public/charts/'.$pngFileName;

if(file_exists($pngFilePath)) {
    $data['chart'] = Helper::env('PUBLIC_URL', '') . "/public/charts/$pngFileName";
    return $data;
}

$currDateTime = new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
$currDateTimeStr = $currDateTime->format('Y-m-d H:i:s');
$currDateTime->setTime(0, 0, 0);
$currTimestamp = $currDateTime->getTimestamp();
$endTime = $currTimestamp * 1000;
$startTime = ($currTimestamp - (48 * 3600)) * 1000;

$newosaseApi = new NewosaseApi();
$newosaseApi->setupAuth();
$newosaseApi->request['query'] = [
    'start' => $startTime,
    'end' => $endTime,
    'timeframe' => 'hour',
    'is_formula' => 0
];

$poolingData = $newosaseApi->sendRequest('GET', "/dashboard-service/operation/chart/pooling/$portId");
if(!$poolingData) {
    $fetchErr = $newosaseApi->getErrorMessages()->response;
    $errMsg = isset($fetchErr->message) ? $fetchErr->message : '';
    $errCode = isset($fetchErr->code) ? $fetchErr->code : '';
    throw new \Error("Newosase API error with code:$errCode, message: $errMsg");
}

$newosaseApi->request['query'] = [];
$tresholdData = $newosaseApi->sendRequest('GET', "/dashboard-service/operation/port/treshold/$portId");
if(!$tresholdData) {
    $fetchErr = $newosaseApi->getErrorMessages()->response;
    $errMsg = isset($fetchErr->message) ? $fetchErr->message : '';
    $errCode = isset($fetchErr->code) ? $fetchErr->code : '';
    throw new \Error("Newosase API error with code:$errCode, message: $errMsg");
}

$tresholdTop = null;
$tresholdBottom = null;

if($tresholdData->result->rules && count($tresholdData->result->rules) > 0) {

    $treshold = $tresholdData->result->rules[0];
    $pattern = '/val\s*<\s*(\d+)\s*or\s*val\s*>\s*(\d+)/';

    if(preg_match($pattern, $treshold->rule, $tresholdMatches)) {
        $tresholdBottom = (int)$tresholdMatches[1];
        $tresholdTop = (int)$tresholdMatches[2];
    }
    
}

$dataMax = [ 'title' => 'Maximum', 'color' => '#ff4560', 'dash' => null, 'data' => [] ];
$dataAvg = [ 'title' => 'Average', 'color' => '#11d190', 'dash' => null, 'data' => [] ];
$dataMin = [ 'title' => 'Minimum', 'color' => '#775dd0', 'dash' => null, 'data' => [] ];
$dataLimitT = [ 'title' => 'Batas Atas', 'color' => '#008ffb', 'dash' => '5,3', 'data' => [] ];
$dataLimitB = [ 'title' => 'Batas Bawah', 'color' => '#775dd0', 'dash' => '5,3', 'data' => [] ];

$isDataEmpty = true;
foreach($poolingData->result as $item) {

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
    return $data;
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

$isApplyLimit = count($dataLimitT['data']) > 0 && count($dataLimitB['data']) > 0;
$chartData = [];

if($isApplyLimit) array_push($chartData, $dataLimitT, $dataLimitB);
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

try {


    if(file_put_contents($svgFilePath, $svgImg)) {

        $cmd = "node /var/www/html/newopnimus/app/CLI/svg-to-png charts/$svgFileName";
        $output = null;

        if(exec($cmd, $output)) {
            
            $data['chart'] = Helper::env('PUBLIC_URL', '') . "/public/charts/$pngFileName";
            return $data;
    
        }
        
        static::sendDebugMessage([
            'status' => 'Cannot write png file',
            'png_path' => realpath($pngFilePath),
            'api' => [
                [
                    'path' => "/dashboard-service/operation/chart/pooling/$portId",
                    'params' => [
                        'start' => $startTime,
                        'end' => $endTime,
                        'timeframe' => 'hour',
                        'is_formula' => 0
                    ]
                ]
            ]
        ]);

    }

    static::sendDebugMessage([
        'status' => 'Cannot write svg file',
        'svg_path' => realpath($svgFilePath),
        'api' => [
            [
                'path' => "/dashboard-service/operation/chart/pooling/$portId",
                'params' => [
                    'start' => $startTime,
                    'end' => $endTime,
                    'timeframe' => 'hour',
                    'is_formula' => 0
                ]
            ]
        ]
    ]);

} catch(\Throwable $err) {
    \MuhammadSabri1306\MyBotLogger\Entities\ErrorLogger::catch($err);
}
return $data;