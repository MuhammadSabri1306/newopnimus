<?php
namespace App\Core;

class CallbackData
{
    public $prefix;
    protected $isClose = false;
    protected $userId = null;
    public $value = null;

    public function __construct(string $controllerPrefix)
    {
        $this->prefix = $controllerPrefix;
    }

    public function limitAccess($telegramUserId)
    {
        if($telegramUserId) {
            $this->userId = $telegramUserId;
            $this->isClose = true;
        }
    }

    public function resetAccess()
    {
        $this->userId = null;
        $this->isClose = false;
    }

    public function hasAccess($telegramUserId)
    {
        $reject = $this->isClose && $telegramUserId != $this->userId;
        return !$reject;
    }

    public function isCallbackOf(array $controllerCallbacks)
    {
        if(!array_key_exists($this->prefix, $controllerCallbacks)) {
            return null;
        }
        return $controllerCallbacks[$this->prefix];
    }

    protected static function encodeData(array $data)
    {
        return urldecode(http_build_query($data, '', '&'));
    }

    protected static function decodeData(string $dataStr)
    {
        parse_str($dataStr, $data);
        return $data;
    }

    public function createEncodedData($value)
    {
        $data = [];
        $data['p'] = $this->prefix;

        if($this->isClose) {
            $data['u'] = $this->userId;
        }

        if(is_string($value) || is_numeric($value) || is_array($value)) {
            $data['v'] = $value;
        }

        $encData = static::encodeData($data);
        if(strlen($encData) > 64) {
            throw new \Exception("The generated Encoded Data is more than 64 characters and should be minimize:'$encData'");
        }

        return $encData;
    }

    public static function decode(string $cbDataStr)
    {
        $cbData = static::decodeData($cbDataStr);
        if(!is_array($cbData) || !isset($cbData['p'])) {
            return null;
        }

        $callbackData = new CallbackData($cbData['p']);
        if(isset($cbData['u'])) {
            $callbackData->limitAccess($cbData['u']);
        }

        if(isset($cbData['v'])) {
            $callbackData->value = $cbData['v'];
        }

        return $callbackData;
    }
}