<?php
namespace App\Libraries\HttpClient;

class ResponseData
{
    protected $data;

    public function __construct($data = null)
    {
        $this->set($data);
    }

    public function set($data)
    {
        $this->data = $data;
    }

    public function get(string $keyNode = null, $defaultValue = null)
    {
        if(!$keyNode) {
            return $this->data;
        }

        if(!is_object($this->data) && !is_array($this->data)) {
            return null;
        }

        $keys = explode('.', $keyNode);
        $data = $this->data;
        for($i=0; $i<count($keys); $i++) {

            $key = $keys[$i];
            if(is_array($data) && isset($data[$key])) {
                $data = $data[$key];
            } elseif(is_object($data) && isset($data->$key)) {
                $data = $data->$key;
            } else {
                $data = $defaultValue;
                $i = count($keys);
            }

        }
        return $data;
    }
}