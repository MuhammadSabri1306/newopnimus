<?php
namespace App\ApiRequest;

use App\Core\RestClient;

class NewosaseApi extends RestClient
{
    public function __construct()
    {
        $this->setBaseUrl('https://newosase.telkom.co.id/api/v1');
        $this->request['verify'] = false;
        $this->request['headers'] = [
            'Accept' => 'application/json'
        ];
    }
}