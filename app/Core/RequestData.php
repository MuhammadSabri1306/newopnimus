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

    public function remove($key)
    {
        unset($this->attributes[$key]);
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

    public function duplicate(...$keys)
    {
        $newInstance = new self();
        foreach($keys as $key) {
            if(isset($this->attributes[$key])) {
                $newInstance->attributes[$key] = $this->attributes[$key];
            }
        }
        return $newInstance;
    }

    public function copy(...$keys)
    {
        $attrs = [];
        foreach($keys as $key) {
            if(isset($this->attributes[$key])) {
                $attrs[$key] = $this->attributes[$key];
            }
        }
        return $attrs;
    }

    public function paste(array $attrs)
    {
        foreach($attrs as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    public function getDebugMessage()
    {
        $reqData = $this->duplicate('parseMode', 'chatId');
        $reqData->text = '```'.PHP_EOL.json_encode($this->build()).'```';
        return $reqData->build();
    }
}
