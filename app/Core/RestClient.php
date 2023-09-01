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

    public function getErrorMessages()
    {
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
            $this->errorMessages['request'] = Psr7\Message::toString($e->getRequest());
            $this->errorMessages['response'] = Psr7\Message::toString($e->getResponse());
            return null;
        } catch (\Exception $e) {
            $this->errorMessages['inline'] = $e->getMessage();
            return null;
        }
    }
}