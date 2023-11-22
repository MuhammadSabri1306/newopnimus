<?php
namespace App\TelegramRequest\AlertStatus;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextSwitchSuccess extends TelegramRequest
{
    public function __construct($alertStatus)
    {
        parent::__construct();
        $this->setData('alert_status', $alertStatus);
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $alertStatus = $this->getData('alert_status', null);

        if($alertStatus === null) {
            return TelegramText::create();
        }

        if($alertStatus == 1) {
            return TelegramText::create('Berhasil menyalakan Alarm kembali.');
        }
        return TelegramText::create('Berhasil mematikan Alarm.');
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}