<?php
namespace App\TelegramRequest\CheckRtu;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;
use App\Core\TelegramRequest\TextList;
use App\Core\TelegramRequest\PortFormat;

class TextWitelsRtuList extends TelegramRequest
{
    use TextList, PortFormat;

    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $rtus = $this->getData('rtus', []);
        $regionalName = $this->getData('regional_name', null);
        $witelName = $this->getData('witel_name', null);

        $text = TelegramText::create()->addText('Status RTU OSASE');
        if($regionalName || $witelName) {
            $text->addText(' di ')
                ->addBold( implode(' ', array_filter([ $witelName, $regionalName ])) );
        }

        $text->addText(' pada ')
            ->addBold( date('Y-m-d H:i:s') )
            ->addText(' WIB adalah:');

        if(count($rtus) < 1) {
            $text->newLine(2)
                ->addItalic(' - Tidak ditemukan adanya RTU');
            return $text;
        }

        $text->startCode();
        foreach($rtus as $rtu) {
            $isRtuUp = strtolower($rtu['status']) == 'normal';
            $text->newLine()
                ->addText($isRtuUp ? '✅' : '❌')
                ->addText($rtu['sname'])
                ->addText(' ('.$rtu['name'].')'.' dalam kondisi ')
                ->addText($isRtuUp ? 'UP' : 'DOWN');
        }

        $text->endCode();
        return $text;
    }

    public function setRtuOfWitel($witels)
    {
        if(is_array($witels)) {

            $rtus = [];
            foreach($witels as $witel) {
                foreach($witel->rtu as $rtu) {
                    array_push($rtus, [
                        'name' => $rtu->rtu_name,
                        'status' => $rtu->rtu_status,
                        'sname' => $rtu->rtu_sname,
                        'location_name' => $rtu->locs_name
                    ]);
                }
            }

            $this->setData('rtus', $rtus);
            $this->params->text = $this->getText()->get();

        }
    }

    public function setWitelName($witelName, $regionalName)
    {
        if(is_string($witelName)) $this->setData('witel_name', $witelName);
        if(is_string($regionalName)) $this->setData('regional_name', $regionalName);
        $this->params->text = $this->getText()->get();
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