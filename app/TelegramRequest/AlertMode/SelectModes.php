<?php
namespace App\TelegramRequest\AlertMode;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectModes extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $alertModes = $this->getData('alert_modes', []);
        $text = TelegramText::create('Silahkan Pilih Mode Alerting berikut.');

        foreach($alertModes as $mode) {
            $text->newLine(2)
                ->addText(' - ')
                ->addBold($mode['title'])->newLine()
                ->addSpace(4)->addItalic($mode['description']);
        }

        return $text;
    }

    public function setAlertModes($alertModes)
    {
        if(is_array($alertModes)) {
            $this->setData('alert_modes', $alertModes);
        }
        $this->params->text = $this->getText()->get();
    }

    public function setInKeyboard(callable $callButton)
    {
        $alertModes = $this->getData('alert_modes', null);

        $inKeyboardItems = array_map(function($mode) use ($callButton) {
            $item = [ 'text' => $mode['title'], 'callback_data' => null ];
            $item = $callButton($item, $mode);
            return [ $item ];
        }, $alertModes);

        // return $inKeyboardItems;
        $this->params->replyMarkup = new InlineKeyboard(...$inKeyboardItems);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}