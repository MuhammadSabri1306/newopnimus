<?php
namespace App\TelegramRequest\Port;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectStatusType extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();

        $this->setData('status_types', [
            [ 'name' => 'Catuan', 'key' => 'a' ]
        ]);
    }

    public function getText()
    {
        return TelegramText::create('Silahkan pilih ')
            ->addBold('Tipe Status')
            ->addText('.');
    }

    public function setInKeyboard(callable $callButton)
    {
        $inlineKeyboardData = array_map(function($type) use ($callButton) {

            $item = $callButton([ 'text' => $type['name'], 'callback_data' => null ], $type);
            return [ $item ];

        }, $this->getData('status_types', []));
        
        $this->params->replyMarkup = new InlineKeyboard(...$inlineKeyboardData);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}