<?php
namespace App\Libraries\HttpClient\Exceptions;

use GuzzleHttp\Exception\ClientException as HttpClientException;

class ClientException extends \Exception
{
    protected $request;
    protected $response;

    public function __construct(HttpClientException $previous = null) {
        parent::__construct($previous->getMessage(), $previous->getCode(), $previous);
        $this->response = json_decode($previous->getResponse()->getBody()->getContents());
        
        $request = $previous->getRequest();

        $this->request = new \stdClass();
        $this->request->uri = (string) $request->getUri();
        $this->request->method = $request->getMethod();
        $this->request->headers = $request->getHeaders();
    }

    public function getRequest() { return $this->request; }
    public function getResponse() { return $this->response; }
}