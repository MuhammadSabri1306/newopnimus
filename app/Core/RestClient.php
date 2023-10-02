<?php
namespace App\Core;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;

class RestClient
{
    public $option = [];
    public $request = []; // Request Option
    private $path = '';
    public $response;
    public $error;

    public $isError = false;
    private $errorMessages = [
        'request' => null,
        'response' => null,
        'inline' => null
    ];

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

    public function getErrorMessages($key = null)
    {
        if($key) {
            return array_key_exists($key, $this->errorMessages) ? $this->errorMessages[$key] : null;
        }
        return (object) $this->errorMessages;
    }

    public function sendRequest(string $httpMethod, string $pathUrl, $associative = null)
    {
        $client = new Client($this->option);
        $pathUrl = $this->path.$pathUrl;
        $requestOption = $this->request;
        try {

            $this->response = $client->request($httpMethod, $pathUrl, $requestOption);
            $body = $this->response->getBody();
            return json_decode($body, $associative);
        
        } catch (ClientException $e) {
            $errRequest = $e->getRequest()->getBody()->getContents();
            $errResponse = $e->getResponse()->getBody()->getContents();
            $this->errorMessages['request'] = json_decode($errRequest, $associative);
            $this->errorMessages['response'] = json_decode($errResponse, $associative);
            return null;
        } catch (\Exception $e) {
            $this->errorMessages['inline'] = $e->getMessage();
            return null;
        }
    }
}