<?php
namespace App\TelegramRequest\Area;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectRtu extends TelegramRequest
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
            ->addBold('RTU')
            ->addText('.');
    }

    public function setInKeyboard(callable $callButton)
    {
        $inlineKeyboardData = array_reduce($this->getData('rtus', []), function($result, $rtu) use ($callButton) {
            $lastIndex = count($result) - 1;
            
            if($lastIndex < 0 || count($result[$lastIndex]) == 3) {
                array_push($result, []);
                $lastIndex++;
            }

            $item = $callButton([ 'text' => $rtu['sname'], 'callback_data' => null ], $rtu);
            array_push($result[$lastIndex], $item);
            return $result;
        }, []);
        
        $this->params->replyMarkup = new InlineKeyboard(...$inlineKeyboardData);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}