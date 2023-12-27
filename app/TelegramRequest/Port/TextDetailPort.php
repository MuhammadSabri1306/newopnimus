<?php
namespace App\TelegramRequest\Port;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;
use App\Helper\DateHelper;

class TextDetailPort extends TelegramRequest
{

    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $port = $this->getData('port', null);
        if(!$port) {
            return TelegramText::create();
        }

        $portName = $port->port_name ?? $port->description;
        $portUnit = htmlspecialchars($port->units);
        $portStatus = $port->severity->name;

        $portUpdatedAt = date('Y-m-d H:i:s', floor($port->updated_at / 1000));
        $currDate = date('Y-m-d H:i:s');
        $duration = DateHelper::dateDiff($portUpdatedAt, $currDate);

        return TelegramText::create()
            ->addText('🧾Berikut detail Port ')
            ->startBold()->addText("$port->no_port")->endBold()
            ->addText(' yang terdapat pada ')
            ->startBold()->addText("$port->rtu_sname ($port->location) $port->witel $port->regional")->endBold()
            ->addText(':')->newLine(2)
            ->startItalic()->addText('Data Diambil pada: '.date('Y-m-d H:i:s').' WIB')->endItalic()->newLine(2)
            ->startCode()
            ->addText("Nama Port : $portName")->newLine()
            ->addText('Tipe Port : -')->newLine()
            ->addText('Jenis Port: -')->newLine()
            ->addText("Satuan    : $portUnit")->newLine()
            ->addText("Value     : $port->value")->newLine()
            ->addText("Status    : $portStatus")->newLine()
            ->addText("Durasi Up : $duration")->newLine()
            ->endCode()->newLine(2)
            ->addText('#OPNIMUS #CEKPORT #PORT\_DETAIL');
    }

    public function setPort($port)
    {
        $this->setData('port', $port);
        $this->params->text = $this->getText()->get();
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}