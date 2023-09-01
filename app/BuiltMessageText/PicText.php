<?php
namespace App\BuiltMessageText;

use App\Core\TelegramText;

class PicText
{
    public static function picAbortInGroup()
    {
        return TelegramText::create()
            ->addText('Mohon maaf, permintaan set PIC Lokasi tidak dapat dilakukan melalui grup. ')
            ->addText('Anda dapat melakukan private chat ')
            ->startBold()->addText('(japri)')->endBold()
            ->addText(' langsung ke bot @opnimusdev_bot dan mengetikkan perintah /setpic, terima kasih.');
    }

    public static function picStatus($user)
    {
        $text = TelegramText::create();

        if(!$user['is_pic']) {
            $text->addText('Anda akan mendaftarkan diri anda menjadi PIC Lokasi. ')
                ->addText('Silahkan memanfaatkan fitur ini apabila anda merupakan pengawal perangkat Network Element Telkom Indonesia di lokasi tertentu.')
                ->newLine(2);
        }

        if(count($user['locations']) < 1) {
            $text->startItalic()->addText('Saat ini anda belum menjadi PIC di lokasi manapun.')->endItalic()->newLine(2);
        } else {
            foreach($user['locations'] as $loc) {
                $locName = $loc['location_name'];
                $locSname = $loc['location_sname'];
                $text->startItalic()
                    ->startBold()->addText("- $locName ($locSname)")->endBold()
                    ->endItalic()->newLine(2);
            }
            $text->newLine();
        }

        $text->addText('Dengan mendaftarkan diri anda sebagai PIC lokasi, anda akan mendapatkan:')->newLine()
            ->addText('📌 ')->startBold()->addText('Alert khusus di lokasi yang anda kawal via japrian OPNIMUS, dan')->endBold()->newLine()
            ->addText('📌 ')->startBold()->addText('Tagging nama anda di grup agar tidak ada alarm yang terlewat dan memudahkan respon.')->endBold()->newLine();
        
        return $text;
    }

    public static function editSelectedLocation(array $locations)
    {
        $text = TelegramText::create('Berikut adalah lokasi yang akan dimonitor:')->newLine(2);
        foreach($locations as $loc) {

            $locName = $loc['location_name'];
            $locSname = $loc['location_sname'];
            $text->addItalic("- $locName ($locSname)")->newLine(2);

        }
        $text->addItalic('Anda dapat memilih maksimal 3 lokasi.');
        return $text;
    }

    public static function registSuccess(array $telgPersUser, array $locations)
    {
        $text = TelegramText::create()
            ->addText('Terima kasih, anda akan didaftarkan sebagai PIC sesuai data berikut.')->newLine(2)
            ->startCode()
            ->addText('Nama Pengguna   : '.$telgPersUser['nama'])->newLine()
            ->addText('No. Handphone   : '.$telgPersUser['telp'])->newLine()
            ->addText('NIK             : '.$telgPersUser['nik'])->newLine()
            ->addText('Status Karyawan : '.($telgPersUser['is_organik'] ? 'Organik' : 'Non Organik'))->newLine()
            ->addText('Nama Instansi   : '.$telgPersUser['instansi'])->newLine()
            ->addText('Unit Kerja      : '.$telgPersUser['unit'])->newLine(2)
            ->addText('Lokasi PIC      : ');

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

    public static function getRegistApprovedText(array $telegramUser)
    {
        $text = TelegramText::create()
            ->addBold('Pengajuan Lokasi PIC berhasil.')->newLine()
            ->addItalic($telegramUser['created_at'])->newLine(2)
            ->addText('Proses pengajuan anda telah mendapat persetujuan Admin. Dengan ini, anda telah merupakan PIC di lokasi berikut.')->newLine(2);

        foreach($telegramUser['locations'] as $loc) {
            $locName = $loc['location_name'];
            $locSname = $loc['location_sname'];
            $text->newLine()->addSpace(4)->addText("- $locSname ($locName)");
        }

        $text->newLine()->addText('Terima kasih.')->newLine()
            ->addText('OPNIMUS, Stay Alert, Stay Safe ')->newLine(2)
            ->addText('#PeduliInfrastruktur #PeduliCME');
        
        return $text;
    }
}