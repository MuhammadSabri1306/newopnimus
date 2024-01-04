<?php

use App\Model\Registration;

$regist = Registration::find($registId);
if(!$regist) {
    return static::sendEmptyResponse();
}

$rejectDate = $regist['updated_at'];

$request = static::request('TextDefault');
$request->params->chatId = $regist['chat_id'];
$request->setText(function($text) use ($rejectDate) {
    return $text->addBold('Pengajuan PIC ditolak.')->newLine()
        ->addItalic($rejectDate)->newLine(2)
        ->addText('Mohon maaf, permintaan anda tidak mendapat persetujuan oleh Admin. ')
        ->addText('Anda dapat berkoordinasi dengan Admin lokal anda untuk mendapatkan informasi terkait.')->newLine()
        ->addText('Terima kasih.');
});
return $request->send();