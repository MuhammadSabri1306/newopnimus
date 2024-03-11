<?php
namespace App\TelegramRequest\ManagementUser;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramText;
use App\Core\TelegramRequest;
use App\Core\TelegramRequest\TextList;
use App\Helper\ArrayHelper;

class TextRemovePic extends TelegramRequest
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
        $users = $this->getData('users', []);
        $levelName = $this->getData('level_name', null);
        if(!$levelName) return new TelegramText();

        $text = TelegramText::create()->addText("Daftar PIC User $levelName")->newLine();

        if(count($users) < 1) {
            
            $text->newLine()->addItalic(' - Belum ada PIC');
            return $text;

        }

        foreach($users as $index => $user) {

            $no = $index + 1;
            $username = $user['username'];

            $name = implode(' ', array_filter([ $user['first_name'], $user['last_name'] ]));
            if(isset($user['full_name'])) $name = $user['full_name'];

            $locsText = implode(', ', $user['loc_snames']);
            if(empty($locsText)) $locsText = ' - ';

            $text->newLine()->addText("$no. ")
                ->addMentionByUsername($user['user_id'], "@$username")
                ->addText(" $name ($locsText)");

        }

        $text->newLine(2)
            ->addText('Silahkan ketik nomor user PIC yang akan direset.');
        return $text;
    }

    public function setLevelName($levelName)
    {
        if(is_string($levelName)) {
            $this->setData('level_name', $levelName);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setUsers($users)
    {
        if(is_array($users)) {
            $this->setData('users', $users);
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