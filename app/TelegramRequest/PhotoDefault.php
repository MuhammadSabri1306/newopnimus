<?php
namespace App\TelegramRequest;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class PhotoDefault extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
    }

    public function setPhoto($photo, $caption = null)
    {
        $this->params->photo = $photo;
        $this->params->caption = $caption ?? '‎';
    }

    public function send(): ServerResponse
    {
        return Request::sendPhoto($this->params->build());
    }
}