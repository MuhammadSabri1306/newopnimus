<?php
namespace App\TelegramRequest\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextUserRejected extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $rejectedDate = $this->getData('rejected_date', null);
        $text = TelegramText::create()
            ->addBold('Pendaftaran Opnimus ditolak.')->newLine();
        if($rejectedDate) $text->addItalic($rejectedDate)->newLine();
        return $text->newLine()
            ->addText('Mohon maaf, permintaan anda tidak mendapat persetujuan oleh Admin. ')
            ->addText('Anda dapat berkoordinasi dengan Admin lokal anda untuk mendapatkan informasi terkait.')->newLine()
            ->addText('Terima kasih.')
            ->get();
    }

    public function setRejectedDate($rejectedDate)
    {
        if(is_string($rejectedDate)) {
            $this->setData('rejected_date', $rejectedDate);
            $this->params->text = $this->getText()->get();
        }
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}