<?php

require __DIR__.'/../app/bootstrap.php';

use App\Model\Regional;
use App\Model\Witel;
use App\Model\RtuLocation;
use App\Model\RtuList;
use App\Controller\BotController;
use App\ApiRequest\NewosaseApi;

$option['value'] = '1092';
$option['title'] = 'BAL';

$request = BotController::request('TextAnswerSelect', [
    BotController::request('Area/SelectLocation')->getText()->get(),
    $option['title']
]);

dd($request);