<?php
namespace App\TelegramRequest\CronAlerting;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextCpuUsage extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $cpuNodeAll = $this->getData('cpu_node_all', null);
        $cpuNodeCron = $this->getData('cpu_node_cron', null);
        $processes = $this->getData('processes', null);

        $text = TelegramText::create();
        if(!$cpuNodeAll && !$cpuNodeCron && !$processes) {
            return $text;
        }
        if(!$processes) $processes = [];

        $text->addBold('Status HOST CPU Node Cron Alerting OPNIMUS')->newLine()
            ->startCode()
            ->addText('CPU Usage:')->newLine()
            ->addText(" - node cron : $cpuNodeCron")->newLine()
            ->addText(" - node all  : $cpuNodeAll");

        if(count($processes) > 0) {
            $text->newLine(2)->addText('Processes List:');
            foreach($processes as $index => $process) {
                $no = str_pad($index + 1, 2, ' ', STR_PAD_LEFT);
                $process = (object) $process;
                $text->newLine(2)
                    ->addText("$no. USER : $process->user")->newLine()
                    ->addText("    PID  : $process->pid")->newLine()
                    ->addText("    %CPU : $process->cpu")->newLine()
                    ->addText("    %MEM : $process->mem")->newLine()
                    ->addText("    CMD  : $process->command");
            }
        }
        $text->endCode();
        return $text;
    }

    public function setCpuUsage($cpuPercentNodeAll, $cpuPercentNodeCron)
    {
        if(!is_null($cpuPercentNodeAll) || $cpuPercentNodeAll === 0) {
            $this->setData('cpu_node_all', "$cpuPercentNodeAll%");
        } else {
            $this->setData('cpu_node_all', json_encode($cpuPercentNodeAll));
        }

        if(!is_null($cpuPercentNodeCron) || $cpuPercentNodeCron === 0) {
            $this->setData('cpu_node_cron', "$cpuPercentNodeCron%");
        } else {
            $this->setData('cpu_node_cron', json_encode($cpuPercentNodeCron));
        }

        $this->params->text = $this->getText()->get();
    }

    public function setCpuProcesses($cpuProcesses)
    {
        if(is_array($cpuProcesses)) {
            $this->setData('processes', $cpuProcesses);
            $this->params->text = $this->getText()->get();
        }
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}