<?php
error_reporting(E_ALL);

require __DIR__.'/../app/bootstrap.php';
use App\ApiRequest\NewosaseApi;

// $rtuId = 33680;
// $currDateTime = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
// $currDateTime->setTime(0, 0, 0);
// $currTimestamp = $currDateTime->getTimestamp();
// $endTime = $currTimestamp * 1000;
// $startTime = ($currTimestamp - (24 * 3600)) * 1000;

// // dd( date('Y-m-d H:i:s', ($startTime / 1000)), date('Y-m-d H:i:s', ($endTime / 1000)) );

// $newosaseApi = new NewosaseApi();
// $newosaseApi->setupAuth();
// $newosaseApi->request['query'] = [
//     'start' => $startTime,
//     'end' => $endTime,
//     'timeframe' => 'hour',
//     'is_formula' => 0
// ];

// $poolingData = $newosaseApi->sendRequest('GET', "/dashboard-service/operation/chart/pooling/$rtuId");
// if(!$poolingData) {
//     $fetchErr = $newosaseApi->getErrorMessages()->response;
//     dd($fetchErr);
// }

// $applyTreshold = false;
// $newosaseApi->request['query'] = [];
// $tresholdData = $newosaseApi->sendRequest('GET', "/dashboard-service/operation/port/treshold/$rtuId");
// if(!$tresholdData) {
//     $fetchErr = $newosaseApi->getErrorMessages()->response;
//     dd($fetchErr);
// }

// $tresholdTop = null;
// $tresholdBottom = null;

// if($tresholdData->result->rules && count($tresholdData->result->rules) > 0) {

//     $treshold = $tresholdData->result->rules[0];
//     $pattern = '/val\s*<\s*(\d+)\s*or\s*val\s*>\s*(\d+)/';

//     if(preg_match($pattern, $treshold->rule, $tresholdMatches)) {
//         $tresholdBottom = (int)$tresholdMatches[1];
//         $tresholdTop = (int)$tresholdMatches[2];
//         $applyTreshold = true;
//     }
    
// }

// $xaxisData = [];
// $avgData = [];
// $maxData = [];
// $minData = [];
// $tresholdTopData = [];
// $tresholdBottomData = [];

// foreach($poolingData->result as $item) {

//     array_push($xaxisData, date('M-d H:i', $item->timestamps / 1000));
//     array_push($avgData, $item->value_avg);
//     array_push($maxData, $item->value_max);
//     array_push($minData, $item->value_min);

//     if($applyTreshold) {
//         array_push($tresholdTopData, $tresholdTop);
//         array_push($tresholdBottomData, $tresholdBottom);
//     }

// }

// $newXaxisItem = date('M-d H:i', strtotime( end($xaxisData) ) + 3600);
// array_push($xaxisData, $newXaxisItem);

// dd_json($xaxisData);
// exit();

/* ============================================================================================ */

// $sampleData = [
//     [ 1699437097, 12.5, 6 ],
//     [ 1699440697, 14, 6 ],
//     [ 1699444297, 17, 8 ],
//     [ 1699447897, 8, 9 ],
// ];

// $sampleData = array_map(function($item) {
//     $item[0] = date('Y-m-d\TH:i', $item[0]);
//     return $item;
// }, $sampleData);

$sampleData = [
    [
        'title' => 'Batas Atas',
        'color' => '#008ffb',
        'dash' => '5,3',
        'data' => [
            '2023-11-08T16:51' => 20,
            '2023-11-08T19:51' => 20,
        ]
    ],
    [
        'title' => 'Batas Bawah',
        'color' => '#775dd0',
        'dash' => '5,3',
        'data' => [
            '2023-11-08T16:51' => 1,
            '2023-11-08T19:51' => 1,
        ]
    ],
    [
        'title' => 'Average',
        // 'color' => '#00e396',
        'color' => '#11d190',
        'dash' => null,
        'data' => [
            '2023-11-08T16:51' => 12.5,
            '2023-11-08T17:51' => 14,
            '2023-11-08T18:51' => 17,
            '2023-11-08T19:51' => 8,
        ]
    ],
    [
        'title' => 'Maximum',
        'color' => '#ff4560',
        'dash' => null,
        'data' => [
            '2023-11-08T16:51' => 13,
            '2023-11-08T17:51' => 15,
            '2023-11-08T18:51' => 20,
            '2023-11-08T19:51' => 9,
        ],
    ],
    [
        'title' => 'Minimum',
        'color' => '#775dd0',
        'dash' => null,
        'data' => [
            '2023-11-08T16:51' => 6,
            '2023-11-08T17:51' => 6,
            '2023-11-08T18:51' => 8,
            '2023-11-08T19:51' => 8,
        ],
    ]
];

// dd($sampleData);

$limitTopColor = '#008ffb';

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

    'fill_under' => array_map(fn($item) => true, $sampleData),
    'label_colour' => '#373d3f',

    'pad_right' => 10,
    'pad_left' => 10,
    'pad_top' => 10,
    'pad_bottom' => 40,

    'marker_type' => array_map(fn($item) => 'circle', $sampleData),
    'marker_size' => 0,
    'marker_colour' => array_map(fn($item) => $item['color'], $sampleData),
    'show_grid_h' => true,
    'show_grid_v' => false,

    'line_curve' => [0.75, 0.9],
    'line_stroke_width' => 2,
    'line_dash' => array_map(fn($item) => $item['dash'], $sampleData),

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
    'legend_entries' => array_map(fn($item) => $item['title'], $sampleData)
];

$graph = new Goat1000\SVGGraph\SVGGraph($imgWidth, $imgHeight, $settings);
$graph->colours(array_map(function($item) {
    $color = $item['color'];
    return [ "$color:0", "$color:0" ];
}, $sampleData));

$graph->values(array_map(fn($item) => $item['data'], $sampleData));
$graph->render('MultiLineGraph');