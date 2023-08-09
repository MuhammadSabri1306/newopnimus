<?php
namespace App\BuiltMessageText;

use App\Core\TelegramText;
use App\Model\Regional;
use App\Model\Witel;

class AdminText
{
    public static function getUserApprovalText($registData)
    {
        $userType = $registData['type'] == 'private' ? 'User' : 'Grup';
        $text = TelegramText::create()
            ->startBold()->addText('Registrasi User OPNIMUS')->endBold()->newLine(2)
            ->addText("Terdapat permintaan registrasi $userType untuk ")
            ->startBold()->addText('menerima Alert')->endBold()->addText(' sesuai data berikut.')->newLine(2)
            ->startCode();

        if($registData['type'] == 'private') {
            $text->addText('Nama User : '.$registData['first_name'].' '.$registData['last_name'])->newLine();
        } else {
            $text->addText('Nama Grup : '.$registData['username'])->newLine();
        }

        $text->addText('Level     : '.ucfirst($registData['level']))->newLine();
        
        $regional = Regional::find($registData['regional_id']);
        if($registData['level'] == 'regional' || $registData['level'] == 'witel') {
            $text->addText('Regional  : '.$regional['name'])->newLine();
        }
        
        $witel = Witel::find($registData['witel_id']);
        if($registData['level'] == 'witel') {
            $text->addText('Witel     : '.$witel['witel_name'])->newLine();
        }

        $text->endCode();
        return $text;
    }
}