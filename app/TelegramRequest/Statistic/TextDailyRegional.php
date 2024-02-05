<?php
namespace App\TelegramRequest\Statistic;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramText;
use App\Core\TelegramRequest;
use App\Core\TelegramRequest\TextList;
use App\Core\TelegramRequest\PortFormat;
use App\Helper\ArrayHelper;

class TextDailyRegional extends TelegramRequest
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
        $regional = $this->getData('regional', null);
        $witelRtuStates = $this->getData('witel_rtu_states', []);
        $portsAlarm = $this->getData('ports_alarm', []);
        $totalRtu = $this->getData('total_rtu', 0);
        $totalPort = $this->getData('total_port', 0);
        $totalOpenedPort = $this->getData('total_opened_port', 0);
        $totalClosedPort = $this->getData('total_closed_port', 0);

        if(!$regional || !is_array($portsAlarm)) {
            return TelegramText::create();
        }

        $regionalName = $regional['name'];
        $currDate = date('Y-m-d H:i:s');

        $text = TelegramText::create()
            ->addText("Statistik HARIAN Anomali Perangkat $regionalName pada $currDate.")->newLine(2)
            ->addBold("〽️STATISTIK $regionalName:")->newLine(2)
            ->addItalic("- TOTAL ALARM RTU  : $totalRtu")->newLine()
            ->addItalic("- TOTAL ALARM PORT : $totalPort")->newLine()
            ->addSpace(7)->addItalic("➥ alarm off: $totalClosedPort")->newLine()
            ->addSpace(7)->addItalic("➥ alarm on: $totalOpenedPort");

        if($totalRtu > 0) {
            $text->newLine(2)
                ->addText('🌟')->addBold('DETAIL RTU DOWN HARI INI');
            foreach($witelRtuStates as $witel) {
                $witelName = $witel['witel_name'];
                $text->newLine(2)->addSpace()->addText("🌇$witelName");
                foreach($witel['rtus'] as $rtu) {
    
                    $rtuSname = $rtu['rtu_sname'];
                    $locName = $rtu['location_name'];
                    $downCount = $rtu['down_count'].'x';
                    $downAt = $rtu['last_down_at'];
                    $text->newLine()->addSpace(8)->addText("- $rtuSname $locName: DOWN $downCount")->newLine()
                        ->addSpace(10)->addItalic("(Last down $downAt)");
    
                }
            }
        }

        if($totalPort > 0) {
            $text->newLine(2)
                ->addText('🎚')->addBold('TOP 10 ALARM PORT HARI INI:')->startCode();
            $maxPortsAlarmIndex = min([ $totalPort, 10 ]);
            for($i=0; $i<$maxPortsAlarmIndex; $i++) {

                if($i > 0) $text->newLine();

                $no = $i + 1;
                $portSeverity = ucfirst($portsAlarm[$i]['last_port_severity']);
                $text->addSpace(2)->addText("$no.($portSeverity)");

                if($portsAlarm[$i]['rtu_sname']) {
                    $rtuSname = $portsAlarm[$i]['rtu_sname'];
                    $text->addText(" $rtuSname");
                }

                if($portsAlarm[$i]['port_name']) {
                    $portName = $portsAlarm[$i]['port_name'];
                    $text->addText(" $portName");
                }

                if($portsAlarm[$i]['location_name']) {
                    $locName = $portsAlarm[$i]['location_name'];
                    $text->addText(" $locName");
                }

                if($portsAlarm[$i]['witel_name']) {
                    $witelName = $portsAlarm[$i]['witel_name'];
                    $text->addText(" $witelName");
                }

                $portValue = $this->toDefaultPortValueFormat(
                    $portsAlarm[$i]['last_port_value'],
                    $portsAlarm[$i]['port_unit'],
                    $portsAlarm[$i]['port_identifier']
                );
                $openedAt = $portsAlarm[$i]['last_opened_at'];
                $openCount = $portsAlarm[$i]['count'].'x';
                $text->addText(" ($portValue - $openedAt): Alarm $openCount");

            }
            $text->endCode();
        }

        return $text;
    }

    public function setRegional($regional)
    {
        $this->setData('regional', $regional);
        $this->params->text = $this->getText()->get();
    }

    public function setAlarmStat($alarmStat)
    {
        if(is_array($alarmStat)) {
            if(isset($alarmStat['rtu_states'])) {
                $witelRtuStates = [];
                foreach($alarmStat['rtu_states'] as $rtu) {
                    $witelIndex = ArrayHelper::findIndex($witelRtuStates, fn($item) => $item['witel_id'] == $rtu['witel_id']);
                    if($witelIndex < 0) {
                        $witel = ArrayHelper::duplicateByKeysRegex($rtu, '/^(witel_|regional_)/');
                        $witelIndex = count($witelRtuStates);
                        array_push($witelRtuStates, [
                            ...$witel,
                            'rtus' => []
                        ]);
                    }
                    array_push($witelRtuStates[$witelIndex]['rtus'], $rtu);
                }
                $this->setData('witel_rtu_states', $witelRtuStates);
            }
            if(isset($alarmStat['ports_alarm'])) {
                $portsAlarm = ArrayHelper::sort($alarmStat['ports_alarm'], fn($a, $b) => [ $b['count'], $a['count'] ]);
                $this->setData('ports_alarm', $portsAlarm);
            }
            if(isset($alarmStat['total_rtu'])) $this->setData('total_rtu', $alarmStat['total_rtu']);
            if(isset($alarmStat['total_port'])) $this->setData('total_port', $alarmStat['total_port']);
            if(isset($alarmStat['total_opened_port'])) $this->setData('total_opened_port', $alarmStat['total_opened_port']);
            if(isset($alarmStat['total_closed_port'])) $this->setData('total_closed_port', $alarmStat['total_closed_port']);
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