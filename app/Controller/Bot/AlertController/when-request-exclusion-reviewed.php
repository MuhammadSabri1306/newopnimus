<?php

use App\Model\Registration;

$regist = Registration::find($registId);
if(!$regist) return static::sendEmptyResponse();

$request = static::request('TextDefault');
$request->setTarget( static::getRequestTarget() );

$reviewDate = $regist['updated_at'];
if($isApproved) {
    $request->setText(function($text) use ($reviewDate) {
        return $text->addBold('Pengajuan Penambahan Alerting diterima.')->newLine()
            ->addItalic($reviewDate)->newLine(2)
            ->addText('Permintaan terkait penambahan alerting telah mendapat persetujuan Admin. ')
            ->addText('Dengan ini Alerting Grup telah dinyalakan.')->newLine()
            ->addText('Terima kasih.');
    });
} else {
    $request->setText(function($text) use ($reviewDate) {
        return $text->addBold('Pengajuan Penambahan Alerting ditolak.')->newLine()
            ->addItalic($reviewDate)->newLine(2)
            ->addText('Mohon maaf, permintaan tidak mendapat persetujuan Admin. ')
            ->addText('Anda dapat berkoordinasi dengan Admin untuk mendapatkan informasi terkait.')->newLine()
            ->addText('Terima kasih.');
    });
}

return $request->send();