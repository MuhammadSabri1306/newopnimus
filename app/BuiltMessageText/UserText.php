<?php
namespace App\BuiltMessageText;

use App\Core\TelegramText;
use App\Core\Conversation;
use App\Model\Regional;
use App\Model\Witel;

class UserText
{
    public static function getRegistSuccessText(Conversation $conversation)
    {
        $isPrivateChat = $conversation->type == 'private';
        $text = TelegramText::create()
            ->addText('Terima kasih, grup akan didaftarkan sesuai data berikut.')->newLine(2)
            ->startCode();

        if($isPrivateChat) {
            $text->addText("Nama Pengguna   : $conversation->fullName")->newLine();
            $text->addText("No. Handphone   : $conversation->telp")->newLine();
        } else {
            $text->addText("Nama Grup       : $conversation->username")->newLine();
        }
        
        $text->addText('Level           : '.ucfirst($conversation->level))->newLine();
        
        if($conversation->level == 'regional' || $conversation->level == 'witel') {
            $regional = Regional::find($conversation->regionalId);
            $text->addText('Regional        : '.$regional['name'])->newLine();
        }
        
        if($conversation->level == 'witel') {
            $witel = Witel::find($conversation->witelId);
            $text->addText('Witel           : '.$witel['witel_name'])->newLine();
        }
        
        if(!$isPrivateChat) {
            $text->addText("Deskripsi Grup  : $conversation->groupDescription")->newLine();
        } else {
            $text->addText("NIK             : $conversation->nik")->newLine();
            $text->addText('Status Karyawan : '.($conversation->isOrganik ? 'Organik' : 'Non Organik'))->newLine();
            $text->addText("Nama Instansi   : $conversation->instansi")->newLine();
            $text->addText("Unit Kerja      : $conversation->unit")->newLine();
        }

        $text->endCode()->newLine()
            ->addText('Silahkan menunggu Admin untuk melakukan verifikasi terhadap permintaan anda, terima kasih.')->newLine(2)
            ->startItalic()->addText('OPNIMUS, Stay Alert, Stay Safe')->endItalic();
        
        return $text;
    }

    public static function getRegistApprovedText(string $approvedAt)
    {
        return TelegramText::create()
            ->startBold()->addText('Pendaftaran Opnimus berhasil.')->endBold()->newLine()
            ->startItalic()->addText($approvedAt)->endItalic()->newLine(2)
            ->addText('Proses pendaftaran anda telah mendapat persetujuan Admin. Dengan ini, lokasi-lokasi yang memiliki RTU Osase akan memberi informasi lengkap mengenai Network Element anda. Apabila ada alarm atau RTU yang down akan langsung dilaporkan ke grup ini.')->newLine()
            ->addText('Untuk mengecek alarm kritis saat ini, pilih /alarm')->newLine()
            ->addText('Untuk melihat statistik RTU beserta MD nya pilih /rtu')->newLine()
            ->addText('Untuk bantuan dan daftar menu pilih /help.')->newLine()
            ->addText('Terima kasih.')->newLine(2)
            ->addText('OPNIMUS, Stay Alert, Stay Safe ')->newLine(2)
            ->addText('#PeduliInfrastruktur #PeduliCME');
    }
}