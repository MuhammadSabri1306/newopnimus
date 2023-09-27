<?php
namespace App\TelegramRequest\Action;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\ChatAction;
use App\Core\TelegramRequest;

class Typing extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->action = ChatAction::TYPING;
    }

    public function send(): ServerResponse
    {
        return Request::sendChatAction($this->params->build());
    }
}