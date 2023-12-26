<?php
namespace App\TelegramRequest\Action;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;

class DeleteMessage extends TelegramRequest
{
    public function __construct($messageId = null, $chatId = null)
    {
        parent::__construct();
        $this->params->messageId = $messageId;
        $this->params->chatId = $chatId;
    }

    public function send(): ServerResponse
    {
        return Request::deleteMessage($this->params->build());
    }
}