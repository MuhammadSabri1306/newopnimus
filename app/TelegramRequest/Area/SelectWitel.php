<?php
namespace App\TelegramRequest\Area;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectWitel extends TelegramRequest
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
            ->addBold('Witel')
            ->addText('.');
    }

    public function setWitels($witels)
    {
        $this->setData('witels', $witels);
    }

    public function setInKeyboard(callable $callButton)
    {
        $inlineKeyboardData = array_map(function($witel) use ($callButton) {

            $title = '';
            if(isset($witel['title'])) $title = $witel['title'];
            elseif(isset($witel['witel_name'])) $title = $witel['witel_name'];

            $item = $callButton([ 'text' => $title, 'callback_data' => null ], $witel);
            return [ $item ];

        }, $this->getData('witels', []));
        
        $this->params->replyMarkup = new InlineKeyboard(...$inlineKeyboardData);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}