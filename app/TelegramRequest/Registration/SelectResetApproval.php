<?php
namespace App\TelegramRequest\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectResetApproval extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $user = $this->getData('user', null);
        $regional = $this->getData('regional', null);
        $witel = $this->getData('witel', null);

        $text = TelegramText::create();

        if(!$user) return $text;
        if($user['level'] != 'nasional' && !$regional) return $text;
        if(($user['level'] == 'witel' || $user['level'] == 'pic') && !$witel) return $text;

        $userText = $user['type'] == 'private' ? 'Anda' : 'Grup ini';
        $text->addText("$userText akan melakukan reset dari BOT OPNIMUS dengan data sebagai berikut.")
            ->newLine(2)
            ->startCode();
        
        if($user['level'] != 'nasional') {
            $text->addText('🏢Regional: '.$regional['name'])->newLine();
        }

        if($user['level'] == 'witel' || $user['level'] == 'pic') {
            $text->addText('🌇Witel   : '.$witel['witel_name'])->newLine();
        }

        if($user['level'] == 'nasional' || $user['level'] == 'pic') {
            $text->addText('Level     : '.strtoupper($user['level']))->newLine();
        }

        $text->endCode()->newLine()
            ->addText("Dengan melakukan reset, $userText akan berhenti menggunakan layanan OPNIMUS. Lanjutkan?");
            
        return $text;
    }

    public function setUser($user)
    {
        $this->setData('user', $user);
        $this->params->text = $this->getText()->get();
    }

    public function setRegional($regional)
    {
        $this->setData('regional', $regional);
        $this->params->text = $this->getText()->get();
    }

    public function setWitel($witel)
    {
        $this->setData('witel', $witel);
        $this->params->text = $this->getText()->get();
    }

    public function setInKeyboard(callable $callButton)
    {
        $inKeyboardData = $callButton([
            'yes' => ['text' => '📵 Reset', 'callback_data' => null],
            'no' => ['text' => '❎ Batalkan', 'callback_data' => null],
        ]);
        
        $this->params->replyMarkup = new InlineKeyboard([ $inKeyboardData['yes'], $inKeyboardData['no'] ]);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}