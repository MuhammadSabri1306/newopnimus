<?php
namespace App\TelegramRequest\Port;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;
use App\Core\TelegramRequest\TextList;
use App\Core\TelegramRequest\PortFormat;
use App\Core\TelegramRequest\RtuFormat;
use App\Helper\ArrayHelper;

class TextPortStatusCatuan extends TelegramRequest
{
    use TextList, PortFormat, RtuFormat;

    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getCurrDateText()
    {
        list($year, $month, $day) = explode('-', date('Y-n-j'));
        $months = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desamber'
        ];

        $monthIndex = intval($month) - 1;
        $monthName = $months[$monthIndex];
        return "$day $monthName $year";
    }

    public function getText()
    {
        $witel = $this->getData('witel', null);
        $rtuPorts = $this->getData('rtu_ports', []);

        if(!$witel) return new TelegramText();
        $text = TelegramText::create('🔆Status Catuan ')
            ->addBold($witel['witel_name'])->addText(' :')->newLine();

        if(count($rtuPorts) < 1) {
            $text->newLine()->addItalic('- Tidak ada RTU yang terdeteksi.');
            return $text;
        }

        $defaultPortTexts = [
            'pln' => TelegramText::create()->addItalic('Tidak ada Port PLN')->get(),
            'genset' => TelegramText::create()->addItalic('Tidak ada Port Genset')->get(),
        ];

        $no = 0;
        foreach($rtuPorts as $rtu) {

            $no++;
            $rtuSname = $rtu['rtu_sname'];
            $locName = $rtu['location'];
            $isPlnOn = false;
            $isGensetOff = false;

            $portTexts = $defaultPortTexts;
            foreach($rtu['ports'] as $port) {
                $portIcon = $this->getAlarmIcon('', '', $port->severity->name);
                $portValue = $this->toDefaultPortValueFormat($port->value, $port->units, $port->identifier);
                if($this->isPlnPort($port->units, $port->identifier)) {
                    $portTexts['pln'] = "$portIcon PLN $portValue";
                    if($port->value !== null) {
                        $isPlnOn = !boolval($port->value);
                    }
                } elseif($this->isGensetPort($port->units, $port->identifier)) {
                    $portTexts['genset'] = "$portIcon Genset $portValue";
                    if($port->value !== null) {
                        $isGensetOff = !boolval($port->value);
                    }
                }
            }

            $status = $isPlnOn && $isGensetOff ? 'NORMAL ✅'
                : ( !$isPlnOn && !$isGensetOff ? 'CRITICAL ❗️' : 'ALERT ⚠️');
            $text->newLine(2)
                ->addBold("$no ⛽️$rtuSname ($locName) : $status")->newLine()
                ->addText($portTexts['pln'])
                ->addText(' | ')
                ->addText($portTexts['genset']);

        }

        $text->newLine(2)->addInlineCode( $this->getCurrDateText() );
        return $text;
    }

    public function setWitel($witel)
    {
        if(is_array($witel)) {
            $this->setData('witel', $witel);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setPorts(array $ports)
    {
        if(is_array($ports)) {
            $rtus = [];
            foreach($ports as $port) {

                $rtuSname = $port->rtu_sname;
                if(!isset($rtus[$rtuSname])) {
                    $rtus[$rtuSname] = [
                        'rtu_sname' => $rtuSname,
                        'rtu_name' => $port->rtu_name,
                        'rtu_status' => $port->rtu_status,
                        'location' => $port->location,
                        'ports' => []
                    ];
                }

                $isGensetPort = $this->isGensetPort($port->units, $port->identifier);
                $isPlnPort = !$isGensetPort && $this->isPlnPort($port->units, $port->identifier);
                if($isGensetPort || $isPlnPort) {
                    array_push($rtus[$rtuSname]['ports'], $port);
                }

            }

            $this->setData('rtu_ports', $rtus);
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