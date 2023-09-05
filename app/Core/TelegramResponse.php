<?php
namespace App\Core;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\RequestData;

abstract class TelegramResponse
{
    abstract public function send(): ServerResponse;
}