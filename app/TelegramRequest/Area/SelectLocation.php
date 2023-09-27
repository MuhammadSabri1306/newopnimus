<?php
namespace App\TelegramRequest\Area;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectLocation extends TelegramRequest
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
            ->addBold('Lokasi')
            ->addText('.');
    }

    public function setInKeyboard(callable $callButton)
    {
        $inlineKeyboardData = array_reduce(
            $this->getData('locations', []),
            function($result, $loc) use ($callButton) {            
            
                $lastIndex = count($result) - 1;
                if($lastIndex < 0 || count($result[$lastIndex]) > 2) {
                    array_push($result, []);
                    $lastIndex++;
                }

                $resultItem = $callButton([ 'text' => $loc['location_sname'], 'callback_data' => null ], $loc);
                array_push($result[$lastIndex], $resultItem);
                return $result;

            },
            []
        );

        $this->params->replyMarkup = new InlineKeyboard(...$inlineKeyboardData);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}