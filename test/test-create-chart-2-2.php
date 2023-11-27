<?php
error_reporting(E_ALL);

require __DIR__.'/../app/bootstrap.php';
use App\ApiRequest\NewosaseApi;

$portId = 33748; // A-07 PNK
$currDateTime = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$currDateTime->setTime(0, 0, 0);
$currTimestamp = $currDateTime->getTimestamp();
$endTime = $currTimestamp * 1000;
$startTime = ($currTimestamp - (24 * 3600)) * 1000;

// // dd( date('Y-m-d H:i:s', ($startTime / 1000)), date('Y-m-d H:i:s', ($endTime / 1000)) );

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
    dd($fetchErr);
}

$applyTreshold = false;
$newosaseApi->request['query'] = [];
$tresholdData = $newosaseApi->sendRequest('GET', "/dashboard-service/operation/port/treshold/$portId");
if(!$tresholdData) {
    $fetchErr = $newosaseApi->getErrorMessages()->response;
    dd($fetchErr);
}

$tresholdTop = null;
$tresholdBottom = null;

if($tresholdData->result->rules && count($tresholdData->result->rules) > 0) {

    $treshold = $tresholdData->result->rules[0];
    $pattern = '/val\s*<\s*(\d+)\s*or\s*val\s*>\s*(\d+)/';

    if(preg_match($pattern, $treshold->rule, $tresholdMatches)) {
        $tresholdBottom = (int)$tresholdMatches[1];
        $tresholdTop = (int)$tresholdMatches[2];
        $applyTreshold = true;
    }
    
}

$chartData = [];
$dataMax = [ 'title' => 'Maximum', 'color' => '#ff4560', 'dash' => null, 'data' => [] ];
$dataAvg = [ 'title' => 'Average', 'color' => '#11d190', 'dash' => null, 'data' => [] ];
$dataMin = [ 'title' => 'Minimum', 'color' => '#775dd0', 'dash' => null, 'data' => [] ];

if($applyTreshold) {

    $chartStartDate = date('Y-m-d\TH:i', $startTime);
    $chartEndDate = date('Y-m-d\TH:i', $endTime);

    if($tresholdTop) {
        $tresholdTopData = [];
        $tresholdTopData[$chartStartDate] = $tresholdTop;
        $tresholdTopData[$chartEndDate] = $tresholdTop;

        array_push($chartData, [
            'title' => 'Batas Atas',
            'color' => '#008ffb',
            'dash' => '5,3',
            'data' => $tresholdTopData
        ]);
    }

    if($tresholdBottom) {
        $tresholdBottomData = [];
        $tresholdBottomData[$chartStartDate] = $tresholdBottom;
        $tresholdBottomData[$chartEndDate] = $tresholdBottom;

        array_push($chartData, [
            'title' => 'Batas Bawah',
            'color' => '#775dd0',
            'dash' => '5,3',
            'data' => $tresholdBottomData
        ]);
    }

}

foreach($poolingData->result as $item) {

    $itemDate = date('Y-m-d\TH:i', $item->timestamps / 1000);

    if(isset($item->value_max)) {
        $dataMax['data'][$itemDate] = $item->value_max;
    }

    if(isset($item->value_avg)) {
        $dataAvg['data'][$itemDate] = $item->value_avg;
    }

    if(isset($item->value_min)) {
        $dataMin['data'][$itemDate] = $item->value_min;
    }

}

array_push($chartData, $dataMax, $dataAvg, $dataMin);
// dd_json($chartData);
// exit();

/* ============================================================================================ */

$imgWidth = 300;
$imgHeight = 200;

$settings = [
    'auto_fit' => true,
    // 'back_colour' => '#eee',
    'back_colour' => '#fff',
    // 'back_colour' => 'none',
    'back_stroke_width' => 0,
    'back_stroke_colour' => '#eee',

    'axis_colour' => '#f4f4f4',
    'axis_text_colour' => '#373d3f',
    'axis_overlap' => 2,
    'grid_colour' => '#f4f4f4',
    'axis_font' => 'Arial',
    'axis_font_size' => 6,
    'axis_text_angle_h' => -45,
    // 'axis_text_back_colour_h' => '#fff',

    'fill_under' => array_map(fn($item) => true, $chartData),
    'label_colour' => '#373d3f',

    'pad_right' => 10,
    'pad_left' => 10,
    'pad_top' => 10,
    'pad_bottom' => 40,

    'marker_type' => array_map(fn($item) => 'circle', $chartData),
    'marker_size' => 0,
    'marker_colour' => array_map(fn($item) => $item['color'], $chartData),
    'show_grid_h' => true,
    'show_grid_v' => false,

    'line_curve' => 0.4,
    'line_stroke_width' => 2,
    'line_dash' => array_map(fn($item) => $item['dash'], $chartData),

    // 'show_shadow' => true,
    // 'shadow_blur' => 1,
    // 'shadow_opacity' => 0.3,
    // 'shadow_offset_x' => 0,
    // 'shadow_offset_y' => 0,

    'datetime_keys' => true,
    'datetime_text_format' => 'M-d H:i',

    'legend_columns' => 3,
    'legend_entry_height' => 10,
    'legend_text_side' => 'left',
    'legend_position' => 'outer bottom 40 -10',
    'legend_font_size' => 6,
    'legend_stroke_width' => 0,
    'legend_shadow_opacity' => 0,
    'legend_draggable' => false,
    'legend_back_colour' => '#fff',
    'legend_entries' => array_map(fn($item) => $item['title'], $chartData)
];

$graph = new \Goat1000\SVGGraph\SVGGraph($imgWidth, $imgHeight, $settings);
$graph->colours(array_map(function($item) {
    $color = $item['color'];
    return [ "$color:0", "$color:0" ];
}, $chartData));

$graph->values(array_map(fn($item) => $item['data'], $chartData));

header('Content-type: image/svg+xml');
$graph->render('MultiLineGraph');

// $svgStr = $graph->fetch('MultiLineGraph');
// $chartSvg = \SVG\SVG::fromString($svgStr);
// $chartPng = $chartSvg->toRasterImage($imgWidth, $imgHeight);

// header('Content-Type: image/png');
// imagepng($chartPng);