<?php
error_reporting(E_ALL);

// https://github.com/HuasoFoundries/jpgraph/blob/master/Examples/examples_theme/no_test_fusion_example.php
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__ . '/../vendor/amenadiel/jpgraph/src/config.inc.php';
use App\ApiRequest\NewosaseApi;
use Amenadiel\JpGraph\Graph;
use Amenadiel\JpGraph\Plot;

$rtuId = 33680;
$currDateTime = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$currDateTime->setTime(0, 0, 0);
$currTimestamp = $currDateTime->getTimestamp();
$endTime = $currTimestamp * 1000;
$startTime = ($currTimestamp - (24 * 3600)) * 1000;

// dd( date('Y-m-d H:i:s', ($startTime / 1000)), date('Y-m-d H:i:s', ($endTime / 1000)) );

$newosaseApi = new NewosaseApi();
$newosaseApi->setupAuth();
$newosaseApi->request['query'] = [
    'start' => $startTime,
    'end' => $endTime,
    'timeframe' => 'hour',
    'is_formula' => 0
];

$poolingData = $newosaseApi->sendRequest('GET', "/dashboard-service/operation/chart/pooling/$rtuId");
if(!$poolingData) {
    $fetchErr = $newosaseApi->getErrorMessages()->response;
    dd($fetchErr);
}

$applyTreshold = false;
$newosaseApi->request['query'] = [];
$tresholdData = $newosaseApi->sendRequest('GET', "/dashboard-service/operation/port/treshold/$rtuId");
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

$xaxisData = [];
$avgData = [];
$maxData = [];
$minData = [];
$tresholdTopData = [];
$tresholdBottomData = [];

foreach($poolingData->result as $item) {

    array_push($xaxisData, date('M-d H:i', $item->timestamps / 1000));
    // array_push($xaxisData, $item->timestamps / 1000);
    array_push($avgData, $item->value_avg);
    array_push($maxData, $item->value_max);
    array_push($minData, $item->value_min);

    if($applyTreshold) {
        array_push($tresholdTopData, $tresholdTop);
        array_push($tresholdBottomData, $tresholdBottom);
    }

}

$newXaxisItem = date('M-d H:i', strtotime( end($xaxisData) ) + 3600);
array_push($xaxisData, $newXaxisItem);

// dd_json($xaxisData);
// exit();

/* ============================================================================================ */

$imgWidth  = 500;
$imgHeight = 500;

$graph = new Graph\Graph($imgWidth, $imgHeight);
$graph->SetMargin(40, 30, 60, 80);

if($applyTreshold) {

    $scaleBottom = floor($tresholdBottom / 20) * 20;
    $scaleTop = ceil($tresholdTop / 20) * 20;
    $graph->SetScale('intlin', $scaleBottom, $scaleTop, 0, 0);

} else {
    $graph->SetScale('intlin');
}

// $graph->img->SetAntiAliasing(false);
$graph->legend->SetMarkAbsSize(12);
$graph->legend->SetMarkAbsVSize(12);
$graph->legend->SetMarkAbsHSize(12);
$graph->legend->SetPos(0.5, 0.02, 'center', 'top');
// $graph->title->Set('Example on TimeStamp Callback');

function yLabelFormat($label) {
    return $label;
}

$graph->yaxis->HideZeroLabel();
$graph->yaxis->HideLine(false);
$graph->yaxis->HideTicks(false, false);
$graph->yaxis->scale->ticks->Set(20, 10);
$graph->yaxis->SetLabelFormatCallback('yLabelFormat');

$graph->xaxis->SetTickLabels($xaxisData);
$graph->xaxis->SetLabelAngle(45);
$graph->xaxis->SetFont(FF_ARIAL, FS_NORMAL, 8);
$graph->xaxis->SetLabelAlign('center', 'top');

$lineAvg = new Plot\LinePlot($avgData);
$graph->Add($lineAvg);
$lineAvg->SetLegend('Average');
$lineAvg->SetStyle('solid');
$lineAvg->SetColor('#00e396');
$lineAvg->SetWeight(2);
$lineAvg->mark->SetType(MARK_FILLEDCIRCLE);
$lineAvg->mark->SetFillColor('#00e396');
$lineAvg->mark->SetSize(0);

$lineMax = new Plot\LinePlot($maxData);
$graph->Add($lineMax);
$lineMax->SetLegend('Maximum');
$lineMax->SetStyle('solid');
$lineMax->SetColor('#ff4560');
$lineMax->SetWeight(2);
$lineMax->mark->SetType(MARK_FILLEDCIRCLE);
$lineMax->mark->SetFillColor('#ff4560');
$lineMax->mark->SetSize(0);

$lineMin = new Plot\LinePlot($minData);
$graph->Add($lineMin);
$lineMin->SetLegend('Minimum');
$lineMin->SetStyle('solid');
$lineMin->SetColor('#775dd0');
$lineMin->SetWeight(2);
$lineMin->mark->SetType(MARK_FILLEDCIRCLE);
$lineMin->mark->SetFillColor('#775dd0');
$lineMin->mark->SetSize(0);

if($applyTreshold) {

    $lineTreshT = new Plot\LinePlot($tresholdTopData);
    $graph->Add($lineTreshT);
    $lineTreshT->SetLegend('Batas Atas');
    $lineTreshT->SetStyle('dashed');
    $lineTreshT->SetColor('#008ffb');
    $lineTreshT->SetWeight(6);
    $lineTreshT->mark->SetType(MARK_FILLEDCIRCLE);
    $lineTreshT->mark->SetFillColor('#008ffb');
    $lineTreshT->mark->SetSize(0);

    $lineTreshB = new Plot\LinePlot($tresholdBottomData);
    $graph->Add($lineTreshB);
    $lineTreshB->SetLegend('Batas Bawah');
    $lineTreshB->SetStyle('dashed');
    $lineTreshB->SetColor('#feb019');
    $lineTreshB->SetWeight(6);
    $lineTreshB->mark->SetType(MARK_FILLEDCIRCLE);
    $lineTreshB->mark->SetFillColor('#feb019');
    $lineTreshB->mark->SetSize(0);

}

$graph->Stroke();