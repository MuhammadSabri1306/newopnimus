<?php
namespace App\Libraries\HttpClient\Exceptions;

use GuzzleHttp\Exception\RequestException;

class ClientException extends \Exception
{
    protected $request;
    protected $response;

    public function __construct(RequestException $previous = null) {
        parent::__construct($previous->getMessage(), $previous->getCode(), $previous);
        if(!$previous->hasResponse()) {
            $this->response = null;
        } else {
            $this->response = json_decode($previous->getResponse()->getBody()->getContents());
        }
        
        $request = $previous->getRequest();

        $this->request = new \stdClass();
        $this->request->uri = (string) $request->getUri();
        $this->request->method = $request->getMethod();
        $this->request->headers = $request->getHeaders();
    }

    public function getRequest() { return $this->request; }
    public function getResponse() { return $this->response; }
}