<?php
namespace App\TelegramRequest\Alarm;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;
use App\Core\TelegramRequest\TextList;
use App\Core\TelegramRequest\PortFormat;
use App\Core\TelegramRequest\RtuFormat;
use App\Helper\ArrayHelper;

class TextPortRegional extends TelegramRequest
{
    use TextList, PortFormat, RtuFormat;

    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $alarms = $this->getData('alarms', []);
        $regional = $this->getData('regional', null);
        $currDateTime = date('Y-m-d H:i:s');

        if(!$regional) {
            return TelegramText::create();
        }

        if(empty($alarms)) {

            $text = TelegramText::create('✅✅')->addBold('ZERO ALARM')->addText('✅✅')->newLine()
                ->addText('Saat ini tidak ada alarm di ')->addBold($regional['name']);
            $text->addText(' pada ')->addBold($currDateTime)->newLine()
                ->startInlineCode()
                ->addText('Tetap Waspada dan disiplin mengawal Network Element Kita.')
                ->addText(' Semoga Network Element Kita tetap dalam kondisi prima dan terkawal.')
                ->endInlineCode()->newLine(2)
                ->addText('Ketikan /help untuk mengakses menu OPNIMUS lainnya.');
            return $text;

        }

        $text = TelegramText::create('Status alarm OSASE di ')
            ->addBold($regional['name'])
            ->addText(" pada $currDateTime WIB adalah:");

        foreach($alarms as $witelItem) {

            $text->newLine(3)->addBold("🌇 $witelItem->witel");

            foreach($witelItem->rtus as $rtu) {

                $text->newLine(2)
                    ->addBold("⛽️$rtu->rtu_sname ($rtu->location) :")
                    ->startCode();

                if($rtu->is_rtu_off) {
                    $text->addSpace(2)->addText("‼️OFF: $rtu->rtu_sname dalam kondisi OFF");
                } else {
                    foreach($rtu->ports as $portIndex => $port) {

                        $portNo = $port->no_port;
                        $portIcon = $this->getAlarmIcon($portNo, $port->port_name, $port->severity->name);
                        $portStatus = strtoupper($port->severity->name);
                        $portDescr = $port->description;
                        $portValue = $this->toDefaultPortValueFormat($port->value, $port->units, $port->identifier);

                        if($portIndex > 0) $text->newLine();
                        $text->addSpace(2)
                            ->addText($portIcon."$portStatus: ($portNo) $portDescr ($portValue)");
                        if(isset($port->alert_start_time)) {
                            $duration = $this->formatTimeDiff($port->alert_start_time);
                            $text->addText(" $duration");
                        }

                    }
                }

                $text->endCode();

            }

        }

        return $text;
    }

    public function formatTimeDiff($timestamp) {
        $currTime = time();
        $timestamp /=  1000;

        $currDateTime = date_create("@$currTime");
        $targetDateTime = date_create("@$timestamp");
        $interval = date_diff($currDateTime, $targetDateTime);

        $formattedTime = '';
        if($interval->d > 0) $formattedTime .= $interval->d . 'd ';
        if($interval->h > 0) $formattedTime .= $interval->h . 'h ';
        if($interval->i > 0) $formattedTime .= $interval->i . 'm ';
        if($interval->s > 0) $formattedTime .= $interval->s . 's';
        return trim($formattedTime);
    }
    
    public function setPorts($ports)
    {
        if(is_array($ports) && count($ports) > 0) {
            $groupData = [];
            foreach($ports as $port) {

                $witelName = $port->witel;
                $rtuName = $port->rtu_name;

                if(!isset($groupData[$witelName])) {
                    $groupData[$witelName] = [
                        'witel' => $port->witel,
                        'regional' => $port->regional,
                        'rtus' => []
                    ];
                }

                if(!isset($groupData[$witelName]['rtus'][$rtuName])) {
                    $groupData[$witelName]['rtus'][$rtuName] = [
                        'rtu_id' => $port->rtu_id,
                        'rtu_name' => $port->rtu_name,
                        'rtu_sname' => $port->rtu_sname,
                        'rtu_status' => $port->rtu_status,
                        'location' => $port->location,
                        'witel' => $port->witel,
                        'regional' => $port->regional,
                        'is_rtu_off' => $this->isRtuStatusOff($port->rtu_status),
                        'ports' => []
                    ];
                }

                if(!$groupData[$witelName]['rtus'][$rtuName]['is_rtu_off']) {
                    array_push($groupData[$witelName]['rtus'][$rtuName]['ports'], $port);
                }
            }

            $alarms = array_map(function($item) {
                $item['rtus'] = ArrayHelper::sortByKey($item['rtus']);
                return $item;
            }, ArrayHelper::sortByKey($groupData));

            $this->setData('alarms', json_decode(json_encode($alarms)));
            $this->params->text = $this->getText()->get();
        }
    }

    public function setRegional($regional)
    {
        if(is_array($regional)) {
            $this->setData('regional', $regional);
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