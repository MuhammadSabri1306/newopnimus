<?php
namespace App\TelegramRequest\Alarm;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;
use App\Core\TelegramRequest\TextList;
use App\Core\TelegramRequest\PortFormat;

class TextPortWitel extends TelegramRequest
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
        $alarms = $this->getData('alarms', []);
        $witel = $this->getData('witel', null);
        $currDateTime = date('Y-m-d H:i:s');

        if(!$witel) {
            return TelegramText::create();
        }

        $text = TelegramText::create('Status alarm OSASE di ')
            ->addBold($witel['witel_name'])
            ->addText(" pada $currDateTime WIB adalah:");

        if(empty($alarms)) {
            $text->newLine(2)->addSpace(4)->addItalic('Belum ada Port RTU berstatus sebagai alarm');
            return $text;
        }

        foreach($alarms as $rtu) {
            $text->newLine(2)
                ->addBold("⛽️$rtu->rtu_sname ($rtu->location) :")
                ->startCode();

            foreach($rtu->ports as $port) {

                $portNo = $port->no_port;
                $portIcon = $this->getAlarmIcon($portNo, $port->port_name, $port->severity->name);
                $portStatus = strtoupper($port->severity->name);
                $portDescr = $port->description;
                $portValue = $this->toDefaultPortValueFormat($port->value, $port->units, $port->identifier);

                $text->newLine()
                    ->addSpace(2)
                    ->addText($portIcon."$portStatus: ($portNo) $portDescr ($portValue)");
                if(isset($port->alert_start_time)) {
                    $duration = $this->formatTimeDiff($port->alert_start_time);
                    $text->addText(" $duration");
                }
            }

            $text->endCode();
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

                $rtuName = $port->rtu_name;
                if(!isset($groupData[$rtuName])) {
                    $groupData[$rtuName] = [
                        'rtu_id' => $port->rtu_id,
                        'rtu_name' => $port->rtu_name,
                        'rtu_sname' => $port->rtu_sname,
                        'rtu_status' => $port->rtu_status,
                        'location' => $port->location,
                        'witel' => $port->witel,
                        'regional' => $port->regional,
                        'ports' => []
                    ];
                }

                array_push($groupData[$rtuName]['ports'], $port);
            }

            $alarms = json_decode(json_encode($groupData));
            $this->setData('alarms', $alarms);
            $this->params->text = $this->getText()->get();
        }
    }

    public function setWitel($witel)
    {
        if(is_array($witel)) {
            $this->setData('witel', $witel);
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