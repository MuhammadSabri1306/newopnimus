<?php
namespace App\TelegramRequest\Area;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectRegional extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        return TelegramText::create('Silahkan pilih ')
            ->addBold('Regional')
            ->addText('.');
    }

    public function setInKeyboard(callable $callButton)
    {
        $inlineKeyboardData = array_map(function($regional) use ($callButton) {

            $item = $callButton([ 'text' => $regional['name'], 'callback_data' => null ], $regional);
            return [ $item ];

        }, $this->getData('regionals', []));
        
        $this->params->replyMarkup = new InlineKeyboard(...$inlineKeyboardData);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}