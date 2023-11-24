<?php
namespace App\TelegramRequest\AlertStatus;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class SelectAdminExclusionApproval extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $registration = $this->getData('registration', null);
        $regional = $this->getData('regional', null);
        $witel = $this->getData('witel', null);
        
        if(!$registration) {
            return TelegramText::create();
        }

        $group = !isset($registration['data'], $registration['data']['request_group']) ? null
            : (object) $registration['data']['request_group'];
        $reqDescr = !isset($registration['data'], $registration['data']['description']) ? ''
            : $registration['data']['description'];
        $existsGroups = !isset($registration['data'], $registration['data']['alerted_groups']) ? []
            : $registration['data']['alerted_groups'];

        $text = TelegramText::create()
            ->addBold('Pengajuan Alerting Opnimus')->newLine(2)
            ->addText('Terdapat permintaan penambahan Alerting grup dengan data berikut.')->newLine(2)
            ->startCode();

        $text->addText("Nama Grup       : $group->username")->newLine();
        $text->addText('Level           : '.ucfirst($group->level))->newLine();
        
        if(in_array($group->level, [ 'regional', 'witel' ]) && $regional) {
            $text->addText('Regional        : '.$regional['name'])->newLine();
        }

        if($group->level == 'witel' && $witel) {
            $text->addText('Witel           : '.$witel['witel_name'])->newLine();
        }
        
        $text->addText("Deskripsi Grup  : $group->group_description")->newLine();
        $text->endCode();

        if($reqDescr) {
            $text->newLine()->addText('Alasan pengajuan:')->newLine()->addText($reqDescr);
        }

        if(count($existsGroups) > 0) {
            $text->newLine(2)->addText('Berikut grup yang telah menerima Alarm.');
            foreach($existsGroups as $item) {
                $text->newLine()->addText(' - '.$item['username']);
            }
        }

        return $text;
    }

    public function setRegistrationData($registration)
    {
        $this->setData('registration', $registration);
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
        $inKeyboardItem = $callButton([
            'approve' => ['text' => '👍 Izinkan', 'callback_data' => null],
            'reject' => ['text' => '❌ Tolak', 'callback_data' => null]
        ]);

        $inlineKeyboardData = [ $inKeyboardItem['approve'], $inKeyboardItem['reject'] ];
        $this->params->replyMarkup = new InlineKeyboard($inlineKeyboardData);
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}