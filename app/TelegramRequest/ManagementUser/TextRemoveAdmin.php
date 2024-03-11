<?php
namespace App\TelegramRequest\ManagementUser;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramText;
use App\Core\TelegramRequest;
use App\Core\TelegramRequest\TextList;
use App\Helper\ArrayHelper;

class TextRemoveAdmin extends TelegramRequest
{
    use TextList;

    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $admins = $this->getData('admins', []);
        $levelName = $this->getData('level_name', null);
        if(!$levelName) return new TelegramText();

        $text = TelegramText::create()->addText("Daftar Admin $levelName")->newLine();

        if(count($admins) < 1) {
            
            $text->newLine()->addItalic(' - Tidak ada Admin');
            return $text;

        }

        foreach($admins as $index => $admin) {

            $no = $index + 1;
            $username = $admin['username'];
            $name = $admin['full_name'] ?? implode(' ', array_filter([ $admin['first_name'], $admin['last_name'] ]));
            $text->newLine()->addText("$no. ")
                ->addMentionByUsername($admin['chat_id'], "@$username")
                ->addText(" $name");

        }

        $text->newLine(2)
            ->addText('Silahkan ketik nomor Admin yang akan dihapus.');
        return $text;
    }

    public function setLevelName($levelName)
    {
        if(is_string($levelName)) {
            $this->setData('level_name', $levelName);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setAdmins($admins)
    {
        if(is_array($admins)) {
            $this->setData('admins', $admins);
            $this->params->text = $this->getText()->get();
        }
    }

    public function send(): ServerResponse
    {
        $text = $this->params->text;
        $messageTextList = $this->splitText($text, 50);

        if(count($messageTextList) < 2) {
            return Request::sendMessage($this->params->build());
        }
        return $this->sendList($messageTextList);
    }
}