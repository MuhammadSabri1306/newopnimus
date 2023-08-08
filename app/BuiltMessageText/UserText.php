<?php
namespace App\BuiltMessageText;

use App\Core\TelegramText;
use App\Model\Regional;
use App\Model\Witel;

class AdminText
{
    public static function getRegistSuccessText(bool $isGroupChat, array $data)
    {
        $text = TelegramText::create();
        $regional = isset($data['regional_id']) ? Regional::find($data['regional_id']) : null;
        $witel = isset($data['witel_id']) ? Witel::find($data['witel_id']) : null;
        $data = (object) $data;

        if($isGroupChat) {
            $text->addText('Terima kasih, grup akan didaftarkan sesuai data berikut.')
                ->startCode()
                ->addText("Nama Grup          : $data->username");
        } else {
            $text->addText('Terima kasih, anda akan didaftarkan sesuai data berikut.')->newLine(2)
                ->startCode()
                ->addText("Nama User          : $data->first_name $data->last_name");
        }

        if($data->level != 'nasional' && $regional) {
            $text->newLine()
                ->addText('Regional           : '.$regional['name']);
        }

        if($data->level == 'witel' && $witel) {
            $text->newLine()
                ->addText('Witel              : '.$witel['witel_name']);
        }

        if($data->level == 'nasional') {
            $text->newLine()
                ->addText('RTU yang dimonitor : Seluruh RTU yang tersedia');
        } else {
            $text->newLine()
                ->addText("RTU yang dimonitor : Seluruh RTU di $data->level ini");
        }

        $text->endCode()->newLine(2);
        if($data->level == 'nasional') {
            $text->addText('Silahkan menunggu Admin NASIONAL untuk melakukan verifikasi terhadap permintaan anda, terima kasih.')->newLine(2);
        } elseif($data->level == 'regional') {
            $text->addText('Silahkan menunggu Admin di '.$regional['name'].' untuk melakukan verifikasi terhadap permintaan anda, terima kasih.')->newLine(2);
        } else {
            $text->addText('Silahkan menunggu Admin di '.$witel['witel_name'].' untuk melakukan verifikasi terhadap permintaan anda, terima kasih.')->newLine(2);
        }
        
        $text->startItalic()->addText('OPNIMUS, Stay Alert, Stay Safe')->endItalic();
        return $text;
    }
}