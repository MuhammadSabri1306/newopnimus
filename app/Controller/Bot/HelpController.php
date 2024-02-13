<?php
namespace App\Controller\Bot;

use App\Controller\BotController;

class HelpController extends BotController
{
    public static function guide()
    {
        $message = static::$command->getMessage();
        $chatId = $message->getChat()->getId();

        $request = static::request('TextDefault');
        $request->params->chatId = $chatId;
        $request->params->remove('parseMode');
        $request->setText(function($text) {
            return $text->addText('🤖WELCOME🤖')->newLine(3)
                ->addText('Selamat datang di pusat bantuan OPNIMUS (Operational Network Infra Monitoring & Surveillance System) versi 2.0 . OPNIMUS adalah sebuah sistem monitoring kondisi kesehatan network element secara realtime dan sistem early warning terhadap potensi gangguan karena permasalahan fisik (catuan, suhu, arus listrik).')->newLine(3)
                ->addText('Berikut daftar perintah dan penjelasan Command OPNIMUS 2.0:')->newLine(2)
                ->addText('1. /help - Berisi daftar Perintah dan penjelasan fungsi OPNIMUS')->newLine()
                ->addText('2. /start - Memulai BOT OPNIMUS')->newLine()
                ->addText('3. /alarm - Menampilkan status alarm saat ini di sesuai level user (Nasional/Regional/Witel)')->newLine()
                ->addText('4. /cekport - Mengecek port pada RTU secara realtime')->newLine()
                ->addText('5. /cekrtu - Mengecek detil RTU (IP, MD, nama RTU, lokasi)')->newLine()
                ->addText('6. /ceklog - Mengecek history gangguan selama satu hari')->newLine()
                ->addText('7. /alert - Menyalakan atau mematikan alert ')->newLine()
                ->addText('    Format Syntax /alert ON untuk menyalakan /alert OFF untuk mematikan')->newLine()
                ->addText('8. /alertmode - Melakukan blasting sesuai kategori berikut:')->newLine(2)
                ->addText(' - Mode 1 (Default)')->newLine()
                ->addText('    Semua tipe alarm akan di-blast.')->newLine(2)
                ->addText(' - Mode 2 (Critical)')->newLine()
                ->addText('    Blasting tegangan drop battery starter, tegangan DC Recti Drop dan suhu ruangan tinggi.')->newLine(2)
                ->addText(' - Mode 3 (Power)')->newLine()
                ->addText('    Alarm Perihal PLN OFF dan GENSET ON.')->newLine(2)
                ->addText('9. /request_admin - Request permintaan untuk menjadi admin Witel, TREG,atau Nasional sesuai dengan profil user')->newLine()
                ->addText('10. /request_alert - Request permintaan khusus apabila memerlukan penambahan alerting dari batasan 1 grup 1 alert. permintaan akan dikirimkan ke Admin')->newLine()
                ->addText('11. /setpic - Set PIC lokasi tertentu, PIC Akan mendapatkan hak untuk menerima alert')->newLine()
                ->addText('12. /statbulanan - statistik bulanan alert pada Witel/Divre')->newLine()
                ->addText('13. /statharian -  statistik harian alert')->newLine()
                ->addText('14. /resetpic - Reset PIC user OPNIMUS, anda dapat menggunakan /setpic untuk mendaftar menjadi PIC kembali')->newLine()
                ->addText('15. /resetuser - Reset User dan keluar dari OPNIMUS')->newLine()
                ->addText('16. /rtu - List daftar RTU dan kondisinya (NORMAL, ALERT, OFF)')->newLine()
                ->addText('17. /listpic - Memunculkan daftar Lokasi dan PIC nya pada witel tertentu')->newLine()
                ->addText('18. /status - Melihat Status Catuan (PLN & GENSET), dan fitur lainnya nantinya')->newLine(2)
                ->addText(' Nantikan fitur-fitur lainnya!, OPNIMUS, Stay Alert, Stay Safe')->newLine(2)
                ->addText(' Apabila memerlukan bantuan, silahkan menghubungi Valliant Ferlyando, Rifqi Fadhlillah & Ghulam Maulana F')->newLine(2);
        });

        return $request->send();
    }
}