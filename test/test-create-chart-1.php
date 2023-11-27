<?php
error_reporting(E_ALL);

require __DIR__.'/../app/bootstrap.php';
use App\ApiRequest\NewosaseApi;
use SVG\SVG;
use SVG\Nodes\Shapes\SVGRect;
use MuhammadSabri1306\PhpChartSvg\ChartSvg;
use MuhammadSabri1306\PhpChartSvg\Graphics\ChartLine;

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

// image with dimensions 100x100
// $image = new SVG(100, 100);
// $doc = $image->getDocument();

// // blue 40x40 square at the origin
// $square = new SVGRect(0, 0, 40, 40);
// $square->setStyle('fill', '#0000FF');
// $doc->addChild($square);

$chart = new ChartSvg(1000, 700);
$chart->setAxisType('x', 'timestamp');
$chart->setAxisType('y', 'numeric');

$chart->yAxis->getAxis()->setStep(10);

$chart->background->setStyle('fill', '#fff');
$chart->background->setStyle('stroke', '#000');
dd($chart);

$lineAvg = new ChartLine([12.5, 14, 17, 8], [1699437097,1699440697, 1699444297, 1699447897]);
$lineAvg->setLegendTitle('Average');
$lineAvg->style()
    ->setColor('#00e396')
    ->setLineWeight(10)
    ->setRounded(true);
$chart->addContent('lineAvg', $lineAvg);

$lineMin = new ChartLine([6, 6, 8, 9], [1699437097,1699440697, 1699444297, 1699447897]);
$lineMin->setLegendTitle('Minimum');
$lineMin->style()
    ->setColor('#775dd0')
    ->setLineWeight(10)
    ->setRounded(true);
$chart->addContent('lineMin', $lineMin);

// stroke-dasharray="5,5"

// dd($lineAvg->getLegend());

// header('Content-Type: image/svg+xml');
echo $chart->getSvg();