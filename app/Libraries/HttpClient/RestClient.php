<?php
namespace App\Libraries\HttpClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Libraries\HttpClient\Exceptions\ClientException;
use App\Libraries\HttpClient\ResponseData;

class RestClient
{
    public $option = [];
    public $request = []; // Request Option
    private $path = '';
    public $response;
    public $responseData;

    public function __construct()
    {
        $this->responseData = new ResponseData();
    }

    protected function setBaseUrl($baseUrl)
    {
        $parts = explode('/', rtrim($baseUrl, '/'));
        if(count($parts) <= 3) {
            $host = $baseUrl;
            $path = '';
        } else {
            $host = $parts[0] . '//' . $parts[2];
            $path = implode('/', array_slice($parts, 3));
        }
        
        $this->option['base_uri'] = $host;
        $this->path = $path;
    }

    protected function getBaseUrl()
    {
        if(empty($this->path)) {
            return $this->option['base_uri'];
        }
        return $this->option['base_uri'].'/'.$this->path;
    }

    public function sendRequest(string $httpMethod, string $pathUrl, $associative = null)
    {
        $client = new Client($this->option);
        $pathUrl = $this->getBaseUrl() . $pathUrl;
        $requestOption = $this->request;
        try {

            $this->response = $client->request($httpMethod, $pathUrl, $requestOption);
            $body = $this->response->getBody();
            $data = json_decode($body, $associative);
            $this->responseData->set($data);
            return $this->responseData;
        
        } catch (RequestException $err) {
            throw new ClientException($err);
        }
    }
}