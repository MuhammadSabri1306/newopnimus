<?php
namespace App\Core;

use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\RequestData;

abstract class TelegramRequest
{
    protected $data = [];
    public $params;

    public function __construct()
    {
        $this->params = new RequestData();
    }

    public function setData($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function getData($key = null, $defaultValue = null)
    {
        if(is_null($key)) {
            return $this->data;
        }

        if(array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return $defaultValue;
    }

    public function setTarget($target)
    {
        if(is_array($target)) {
            if(isset($target['chat_id'])) $this->params->chatId = $target['chat_id'];
            if(isset($target['message_thread_id'])) $this->params->messageThreadId = $target['message_thread_id'];
        }
    }

    abstract public function send(): ServerResponse;
}