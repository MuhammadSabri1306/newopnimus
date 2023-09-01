<?php
namespace App\BuiltMessageText;

use App\Core\TelegramText;
use App\Model\Regional;
use App\Model\Witel;

class AdminText
{
    public static function getUserApprovalText(array $registData)
    {
        $registData = (object) $registData;
        $userData = isset($registData->data) ? (object) $registData->data : null;
        $isPrivateChat = $userData->type == 'private';

        $text = TelegramText::create()
            ->startBold()->addText('Registrasi User OPNIMUS')->endBold()->newLine(2)
            ->addText('Terdapat permintaan registrasi '.($isPrivateChat ? 'User' : 'Grup').' untuk ')
            ->startBold()->addText('menerima Alert')->endBold()->addText(' sesuai data berikut.')->newLine(2)
            ->startCode();

        if($isPrivateChat) {
            $text->addText("Nama Pengguna   : $userData->full_name")->newLine();
            $text->addText("No. Handphone   : $userData->telp")->newLine();
        } else {
            $text->addText("Nama Grup       : $userData->username")->newLine();
        }
        
        $text->addText('Level           : '.ucfirst($userData->level))->newLine();
        
        if($userData->level == 'regional' || $userData->level == 'witel') {
            $regional = Regional::find($userData->regional_id);
            $text->addText('Regional        : '.$regional['name'])->newLine();
        }
        
        if($userData->level == 'witel') {
            $witel = Witel::find($userData->witel_id);
            $text->addText('Witel           : '.$witel['witel_name'])->newLine();
        }
        
        if(!$isPrivateChat) {
            $text->addText("Deskripsi Grup  : $userData->group_description")->newLine();
        } else {
            $text->addText("NIK             : $userData->nik")->newLine();
            $text->addText('Status Karyawan : '.($userData->is_organik ? 'Organik' : 'Non Organik'))->newLine();
            $text->addText("Nama Instansi   : $userData->instansi")->newLine();
            $text->addText("Unit Kerja      : $userData->unit")->newLine();
        }

        $text->endCode();
        return $text;
    }
    
    public static function getPicApprovalText(array $registData)
    {
        $registData = (object) $registData;
        $userData = isset($registData->data) ? (object) $registData->data : null;
        $isPrivateChat = $userData->type == 'private';

        $regional = Regional::find($userData->regional_id);
        $witel = Witel::find($userData->witel_id);

        $text = TelegramText::create()
            ->addBold('Registrasi PIC OPNIMUS')->newLine(2)
            ->addText('Terdapat permintaan registrasi User untuk ')->addBold('menjadi PIC')->addText(' sesuai data berikut.')->newLine(2)
            ->startCode()
            ->addText("Nama Pengguna   : $userData->full_name")->newLine()
            ->addText("No. Handphone   : $userData->telp")->newLine()
            ->addText('Level           : '.ucfirst($userData->level))->newLine()
            ->addText("Regional        : $userData->regional_name")->newLine()
            ->addText("Witel           : $userData->witel_name")->newLine(2)
            ->addText("NIK             : $userData->nik")->newLine()
            ->addText('Status Karyawan : '.($userData->is_organik ? 'Organik' : 'Non Organik'))->newLine()
            ->addText("Nama Instansi   : $userData->instansi")->newLine()
            ->addText("Unit Kerja      : $userData->unit")->newLine()
            ->endCode();
            
        return $text;
    }
}