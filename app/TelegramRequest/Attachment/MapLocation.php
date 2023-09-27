<?php
namespace App\TelegramRequest\Attachment;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;

class MapLocation extends TelegramRequest
{
    public function __construct($latitude, $longitude)
    {
        parent::__construct();
        $this->params->latitude = $latitude;
        $this->params->longitude = $longitude;
    }

    public function send(): ServerResponse
    {
        return Request::sendLocation($this->params->build());
    }
}