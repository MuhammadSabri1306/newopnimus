<?php
require __DIR__.'/../app/bootstrap.php';

// use GuzzleHttp\Client;
// use GuzzleHttp\Psr7;
// use GuzzleHttp\Exception\ClientException;

// $client = new Client([
//     'base_uri' => 'https://newosase.telkom.co.id'
// ]);

// $requestOption = [
//     'query' => [ 'witelId' => 43, 'isAlert' => 1 ],
//     'headers' => [
//         'Accept' => 'application/json',
//         'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhcGlfaWQiOiJ3ckIyRWtrWjQyVUNuZGxsNDI1eCIsInRva2VuIjoicDVjVVRfeTVFeklXUzRrY2VkTFdBUHdXeWlsVkpNZzNSNkdFaEduVW5VakZaS2VlVE8iLCJpYXQiOjE2ODg0NTE0NzAsImV4cCI6MTY4ODUzNzg3MH0.jfygcJVfFpsRm1Jk94dRxWT17RSDcGRpsO6CET63uI8'
//     ],
//     'verify' => false
// ];

// try {

//     $response = $client->request('GET', '/api/v1/dashboard-service/dashboard/rtu/port-sensors', $requestOption);
//     // echo $response->get
//     $body = $response->getBody();
//     $data = json_decode($body);
//     dd($data);

// } catch (ClientException $e) {
//     echo Psr7\Message::toString($e->getRequest());
//     echo Psr7\Message::toString($e->getResponse());
// }

use App\ApiRequest\NewosaseApi;

$newosaseApi = new NewosaseApi();
$newosaseApi->request['query'] = [ 'searchRtuSname' => 'RTU00-D7-ANT' ];
$response = $newosaseApi->sendRequest('GET', '/dashboard-service/dashboard/rtu/port-sensors');

if(!$response) {
    dd($newosaseApi->getErrorMessages());
}

dd($response);