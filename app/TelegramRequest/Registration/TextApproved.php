<?php
namespace App\TelegramRequest\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Core\TelegramRequest;
use App\Core\TelegramTextV2;

class TextApproved extends TelegramRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->params->parseMode = 'MarkdownV2';
        $this->params->text = $this->getText()->get();
    }

    public function getText()
    {
        $approvedAt = $this->getData('approved_at', null);
        $name = $this->getData('name', null);
        $isPrivate = $this->getData('is_private', true);
        $isPic = $this->getData('is_pic', true);
        $pivotArea = $this->getData('pivot_area', null);
        $pivotGroupTitle = $this->getData('pivot_group_title', null);
        $isPivotGroup = $this->getData('is_pivot_group', null);

        if(!$isPic) {
            if(!$pivotArea) return TelegramTextV2::create('Test 1');
            if($isPivotGroup && !$name) return TelegramTextV2::create('Test 2');
        }

        $text = TelegramTextV2::create()
            ->addText('🆗')
            ->addBold('Selamat Pendaftaran OPNIMUS telah APPROVED .')
            ->addText('🆗');

        if($approvedAt) {
            $text->newLine()->addItalic("di approve pada $approvedAt WIB");
        }

        $text->newLine(2)
            ->addText('Proses pendaftaran anda telah mendapat persetujuan Admin.')
            ->addText(' Dengan ini, lokasi-lokasi yang memiliki RTU Osase dan terinput pada ')
            ->addBold('NewOsase New')
            ->addText(' (')->addLink('https://newosase.telkom.co.id/new', 'newosase.telkom.co.id/new')->addText(')')
            ->addText(' akan memberi informasi lengkap mengenai Network Element anda sesuai')
            ->addText(' dengan sensor yang terpasang di RTU.')->newLine(2)
            ->startQuote()
            ->addText('Untuk kehandalan alerting, OPNIMUS hanya melakukan blasting alarm')
            ->addText(' kepada PIC terkait dan Maksimal 1 Grup sesuai Profil (Nasional, regional dan Witel)');
        
        if($isPic) {
            $text->newLine(2)
                ->addText('Alerting anda sudah ')
                ->addBold('Aktif')->addText('. ');
        } elseif($isPivotGroup) {
            $text->newLine(2)
                ->addText('Alerting pada ')
                ->addBold("Grup $name")
                ->addText(' sudah')->addBold('Aktif')
                ->addText(' akan menjadi grup utama blasting alarm OPNIMUS pada ')
                ->addBold($pivotArea)->newLine(2);
        } elseif(!$isPrivate) {
            $text->newLine(2)
                ->addBold($pivotArea)
                ->addText(' saat ini sudah memiliki Grup Alerting utama yaitu di Grup: ')
                ->addBold($pivotGroupTitle)
                ->addText(', sehingga OPNIMUS di grup ini tidak akan mendapatkan alerting,')->newLine(2)
                ->addText('Apabila ada alarm atau RTU yang down akan langsung dilaporkan ke grup ')
                ->addBold($pivotGroupTitle)
                ->addText(' atau anda dapat menghubungi Admin untuk koordinasi penambahan pada grup.')
                ->newLine(2);
        } else {
            $text->newLine();
        }

        $text->addText('Selebihnya silahkan menggunakan command /help untuk melihat daftar command OPNIMUS 2.0')
            ->newLine(2)->addItalic('- OPNIMUS, Stay Alert, Stay Safe -');

        return $text;
    }

    public function setApprovedAt(string $dateStr)
    {
        
        $this->params->text = $this->getText()->get();
    }

    public function setUser($telgUser, $isPivotGroup)
    {
        if(!is_array($telgUser)) return;
        $this->setData('is_private', $telgUser['type'] == 'private');
        if($telgUser['type'] != 'private') {
            $this->setData('name', $telgUser['username']);
        } elseif($telgUser['first_name'] && $telgUser['last_name']) {
            $this->setData('name', $telgUser['first_name'].' '.$telgUser['last_name']);
        } elseif($telgUser['first_name']) {
            $this->setData('name', $telgUser['first_name']);
        } elseif($telgUser['last_name']) {
            $this->setData('name', $telgUser['last_name']);
        }
        $this->setData('is_pic', (bool) $telgUser['is_pic']);
        $this->setData('approved_at', $telgUser['created_at']);
        $this->setData('is_pivot_group', $isPivotGroup);
        $this->params->text = $this->getText()->get();
    }

    public function setPivotArea($pivotArea)
    {
        $this->setData('pivot_area', $pivotArea);
        $this->params->text = $this->getText()->get();
    }

    public function setPivotGroup($pivotGroupTitle)
    {
        $this->setData('pivot_group_title', $pivotGroupTitle);
        $this->params->text = $this->getText()->get();
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->params->build());
    }
}