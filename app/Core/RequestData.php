<?php
namespace App\Core;

class RequestData
{
    private $attributes = [];

    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }
    
    private function transformKey($key)
    {
        $result = preg_replace('/(?<!^)[A-Z]/', '_$0', $key);
        return strtolower($result);
    }

    public function build()
    {
        $data = [];
        foreach($this->attributes as $key => $value) {
            $key = $this->transformKey($key);
            $data[$key] = $value;
        }
        return $data;
    }
}
