<?php
namespace App\TelegramRequest\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextDoneReviewed extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $statusText = $this->getData('status_text', 'disetujui');
        $admin = $this->getData('admin', null);

        $text = TelegramText::create("Permintaan registrasi telah $statusText oleh ");
        if(!$admin) {
            $text->addBold('Admin')->addText('.');
            return $text;
        }

        $adminUserId = $admin['chat_id'] ?? null;
        $adminFirstName = $admin['first_name'] ?? null;
        $adminLastName = $admin['last_name'] ?? null;
        $adminUsername = $admin['username'] ?? null;

        if($adminFirstName && $adminLastName) {
            $text->addText('Admin ')
                ->addMentionByName($adminUserId, "$adminFirstName $adminLastName");
        } elseif($adminUsername) {
            $text->addText('Admin ')
                ->addMentionByUsername($adminUserId, $adminUsername);
        } else {
            $text->addMentionByName($adminUserId, 'Admin');
        }

        if($admin['witel_name']) {
            $text->addItalic(' - '.$admin['witel_name'].'.');
        } elseif($admin['regional_name']) {
            $text->addItalic(' - '.$admin['regional_name'].'.');
        } else {
            $text->addItalic(' - Level NASIONAL.');
        }

        return $text;
    }

    public function setStatusText($statusText)
    {
        $this->setData('status_text', $statusText);
        $this->params->text = $this->getText()->get();
    }

    public function setAdminData($admin)
    {
        if(is_array($admin)) {
            $this->setData('admin', $admin);
            $this->params->text = $this->getText()->get();
        }
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }

    public function sendUpdate(): ServerResponse
    {
        return Request::editMessageText($this->params->build());
    }
}