<?php
namespace App\TelegramRequest\AlertStatus;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextAlertGroupHasExists extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->buildText();
        $this->params->parseMode = 'markdown';
    }

    public function getText()
    {
        $levelName = $this->getData('level_name', null);
        $groupTitle = $this->getData('group_title', null);

        if(!$levelName || !$groupTitle) {
            return TelegramText::create();
        }

        return TelegramText::create("Alarm untuk area $levelName telah didaftarkan pada grup $groupTitle,")
            ->addText(' anda dapat menghubungi Admin untuk koordinasi penambahan pada grup.');
    }

    public function setLevelName(string $levelName)
    {
        $this->setData('level_name', $levelName);
    }

    public function setGroupTitle(string $groupTitle)
    {
        $this->setData('group_title', $groupTitle);
    }

    public function buildText()
    {
        $this->params->text = $this->getText()->get();
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}