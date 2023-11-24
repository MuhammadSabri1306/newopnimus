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
        $this->params->parseMode = 'markdown';
    }

    public function getText()
    {
        $levelName = $this->getData('level_name', null);
        $groupTitle = $this->getData('group_title', null);

        if(!$levelName || !$groupTitle) {
            throw new \Exception("levelName or groupTitle is not set, levelName:$levelName, groupTitle:$groupTitle");
        }

        return TelegramText::create('Alerting Opnimus pada ')
            ->addBold($levelName)->addText(' sudah ada pada grup ')->addBold($groupTitle)->addText('.')->newLine(2)
            ->addText('Kami menerapkan Policy 1 Witel/Regional 1 Alerting untuk efektifitas blasting alarm')
            ->addText(' dan efisiensi sistem alerting.')->newLine()
            ->addText('Terima kasih.');
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