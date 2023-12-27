<?php
require __DIR__.'/../app/bootstrap.php';

// use App\Controller\Bot\PortController;

// $newosaseApiParams = [
//     'searchRtuSname' => 'RTU00-D7-BAL',
//     'searchNoPort' => 'A-03'
// ];

// try {
//     $data = PortController::getNewosasePortDetail($newosaseApiParams);
//     dd($data);
// } catch(\Throwable $err) {
//     echo $err;
// }
// exit();

use App\ApiRequest\NewosaseApi;
use Goat1000\SVGGraph\SVGGraph;
// $portId = 33680;
$portId = 33688;

try {

    $currDateTime = new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
    $currDateTimeStr = $currDateTime->format('Y-m-d H:i:s');
    $currDateTime->setTime(0, 0, 0);
    $currTimestamp = $currDateTime->getTimestamp();
    $endTime = $currTimestamp * 1000;
    $startTime = ($currTimestamp - (48 * 3600)) * 1000;

    // $endTime = 1699418640693;
    // $startTime = 1699159446693;

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

    $applyTreshold = false;
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
            $applyTreshold = true;
        }
        
    }

    $chartData = [];
    $dataMax = [ 'title' => 'Maximum', 'color' => '#ff4560', 'dash' => null, 'data' => [] ];
    $dataAvg = [ 'title' => 'Average', 'color' => '#11d190', 'dash' => null, 'data' => [] ];
    $dataMin = [ 'title' => 'Minimum', 'color' => '#775dd0', 'dash' => null, 'data' => [] ];

    if($applyTreshold) {

        $chartStartDate = date('Y-m-d\TH:i', $startTime / 1000);
        $chartEndDate = date('Y-m-d\TH:i', $endTime / 1000);

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
    echo $svgImg;

    // $svgFileName = "checkport_chart_port$portId.svg";
    // $svgFilePath = __DIR__.'/../public/charts/'.$svgFileName;
    // file_put_contents($svgFilePath, $svgImg);
    
    // $cmd = "node /var/www/html/newopnimus/app/CLI/svg-to-png charts/$svgFileName";
    // $output = null;
    // if(exec($cmd, $output)) {
    //     dd($output);
    // }

} catch(\Throwable $err) {
    echo $err;
}