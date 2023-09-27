<?php
require __DIR__.'/../app/bootstrap.php';

use App\ApiRequest\NewosaseApi;

$rtuId = '1087';
$newosaseApi = new NewosaseApi();
$newosaseApi->setupAuth();
$response = $newosaseApi->sendRequest('GET', "/dashboard-service/operation/rtu/$rtuId");
// $response = $newosaseApi->sendRequest('GET', '/parameter-service/monitoring/summaryrtu?page=1&limit=5&regional_id=%%&witel_id=%%&level=1&location_id=%%&id_tags=%%');

if(!$response) {
    dd_json([
        'error' => $newosaseApi->getErrorMessages()
    ]);
}

dd($response);