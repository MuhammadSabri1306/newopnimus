<?php
namespace App\TelegramRequest\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramText;

class TextApproved extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'markdown';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $approvedAt = $this->getData('approved_at', null);
        $isPrivate = $this->getData('is_private', true);
        $groupTitle = $this->getData('group_title', null);

        $text = TelegramText::create()->addBold('Pendaftaran Opnimus berhasil.');
        if($approvedAt) {
            $text->newLine()->addItalic($approvedAt);
        }

        $text->newLine(2)
            ->addText('Proses pendaftaran anda telah mendapat persetujuan Admin.')
            ->addText(' Dengan ini, lokasi-lokasi yang memiliki RTU Osase akan memberi informasi lengkap mengenai Network Element anda.');

        if($groupTitle) {
            $text->addText(" Apabila ada alarm atau RTU yang down akan langsung dilaporkan ke grup ")
                ->addBold($groupTitle)
                ->addText(' atau anda dapat menghubungi Admin untuk koordinasi penambahan pada grup.');
        } elseif(!$isPrivate) {
            $text->addText(' Apabila ada alarm atau RTU yang down akan langsung dilaporkan ke grup ini.');
        }

        $text->newLine(2)
            ->addText('Untuk mengecek alarm kritis saat ini, pilih /alarm')->newLine()
            ->addText('Untuk melihat statistik RTU beserta MD nya pilih /rtu')->newLine()
            ->addText('Untuk bantuan dan daftar menu pilih /help.')->newLine()
            ->addText('Terima kasih.')->newLine(2)
            ->addText('OPNIMUS, Stay Alert, Stay Safe ')->newLine(2)
            ->addText('#PeduliInfrastruktur #PeduliCME');

        return $text;
    }

    public function setApprovedAt(string $dateStr, bool $refreshText = true)
    {
        $this->setData('approved_at', $dateStr);
        if($refreshText) $this->params->text = $this->getText()->get();
    }

    public function setIsPrivate(bool $isPrivate, bool $refreshText = true)
    {
        $this->setData('is_private', $isPrivate);
        if($refreshText) $this->params->text = $this->getText()->get();
    }

    public function setAlertingGroup(string $groupTitle, bool $refreshText = true)
    {
        $this->setData('group_title', $groupTitle);
        if($refreshText) $this->params->text = $this->getText()->get();
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}