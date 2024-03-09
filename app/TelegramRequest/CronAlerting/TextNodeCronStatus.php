<?php
namespace App\TelegramRequest\CronAlerting;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextNodeCronStatus extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $status = $this->getData('status', []);
        $text = TelegramText::create();
        if(empty($status)) return $text;

        $text->addBold('Status Node Cron Alerting OPNIMUS')->newLine()
            ->startCode()
            ->addText('opnimus-alerting-port-v5');
        foreach($status as $moduleName => $isActive) {
            $statusIcon = $isActive ? '✅' : '⛔️';
            $text->newLine()->addText("  $statusIcon $moduleName");
        }
        $text->endCode();
        return $text;
    }

    public function setStatus($nodeCronStatus)
    {
        if(is_array($nodeCronStatus)) {
            $this->setData('status', $nodeCronStatus);
            $this->params->text = $this->getText()->get();
        }
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}