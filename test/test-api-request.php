<?php
require __DIR__.'/../app/bootstrap.php';

use App\ApiRequest\NewosaseApi;

$rtuId = '1087';
$newosaseApi = new NewosaseApi();
// /parameter-service/monitoring/summaryrtu?page=1&limit=5&regional_id=%%&witel_id=%%&level=1&location_id=%%&id_tags=%%
$newosaseApi->request['query'] = [
    'page' => 1,
    'limit' => 5,
    'regional_id' => '%%',
    'witel_id' => '%%',
    'level' => 1,
    'location_id' => '%%',
    'id_tags' => '%%',
];

$newosaseApi->setupAuth();
$data = $newosaseApi->sendRequest('GET', '/parameter-service/monitoring/summaryrtu');

if(!$data) {
    $fetchErr = $newosaseApi->getErrorMessages()->response;
    dd($fetchErr);
}

dd_json($data);