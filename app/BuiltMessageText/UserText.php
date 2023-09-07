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

    public static function registPicSuccess(array $registration, array $locations)
    {
        $registData = $registration['data'];
        $regional = Regional::find($registData['regional_id']);
        $witel = Witel::find($registData['witel_id']);

        $text = TelegramText::create()
            ->addText('Terima kasih, anda akan didaftarkan sebagai PIC sesuai data berikut.')->newLine(2)
            ->startCode();
            
        $text->addText('Nama Pengguna   : '.$registData['full_name'])->newLine();
        $text->addText('No. Handphone   : '.$registData['telp'])->newLine();
        $text->addText('Level           : '.ucfirst($registData['level']))->newLine();
        $text->addText('Regional        : '.$regional['name'])->newLine();
        $text->addText('Witel           : '.$witel['witel_name'])->newLine(2);
        $text->addText('NIK             : '.$registData['nik'])->newLine();
        $text->addText('Status Karyawan : '.($registData['is_organik'] ? 'Organik' : 'Non Organik'))->newLine();
        $text->addText('Nama Instansi   : '.$registData['instansi'])->newLine();
        $text->addText('Unit Kerja      : '.$registData['unit'])->newLine(2);
        $text->addText('Lokasi PIC      : ');

        foreach($locations as $loc) {
            $locName = $loc['location_name'];
            $locSname = $loc['location_sname'];
            $text->newLine()->addSpace(4)->addText("- $locSname ($locName)");
        }

        $text->endCode()->newLine(2)
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

    public static function unregistedText()
    {
        return TelegramText::create('Anda belum terdaftar sebagai pengguna OPNIMUS.')->newLine()
            ->addText('Anda dapat mengetikkan perintah /start untuk melakukan registrasi sebagai pengguna.');
    }
}