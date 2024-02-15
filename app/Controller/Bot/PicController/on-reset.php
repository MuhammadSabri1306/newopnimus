<?php

use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\Registration;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\PicLocation;
use App\Model\AlertUsers;

$message = static::getMessage();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();

$response = static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();
if($callbackValue != 'continue') return $response;

if(!$message->getChat()->isPrivateChat()) {
            
    $request = static::request('Pic/TextErrorNotInPrivate');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

$telgUser = static::getUser();
if(!$telgUser) {

    $request = static::request('Error/TextUserUnidentified');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

$regist = Registration::query(function($db, $table) use ($chatId) {
    $query = "SELECT * FROM $table WHERE request_type='pic' AND status='unprocessed' AND chat_id=%i";
    $data = $db->queryFirstRow($query, $chatId);
    if(isset($data['data'])) $data['data'] = json_decode($data['data'], true);
    return $data ?? null;
});

$hasCancelRegist = false;
if($regist && $regist['status'] == 'unprocessed') {
    if(isset($registData['approval_messages']) && count($registData['approval_messages']) > 0) {

        $registData = $regist['data'];
        $telgPersUser = TelegramPersonalUser::findByUserId($telgUser['id']);

        $registData['full_name'] = $telgPersUser['nama'];
        $registData['username'] = $telgUser['username'];
        $registData['user_id'] = $telgUser['user_id'];
        $registData['telp'] = $telgPersUser['telp'];
        $registData['level'] = $telgUser['level'];
        $registData['nik'] = $telgPersUser['nik'];
        $registData['is_organik'] = $telgPersUser['is_organik'];
        $registData['instansi'] = $telgPersUser['instansi'];
        $registData['unit'] = $telgPersUser['unit'];

        if($registData['level'] == 'regional' || $registData['level'] == 'witel') {
            $registData['regional_id'] = $telgUser['regional_id'];
        }

        if($registData['level'] == 'witel') {
            $registData['witel_id'] = $telgUser['witel_id'];
        }

        $prevRequest = static::request('RegistPic/SelectAdminApproval');
        $prevRequest->setRegistrationData($registData);
        if(isset($registData['regional_id'])) $prevRequest->setRegional( Regional::find($registData['regional_id']) );
        if(isset($registData['witel_id'])) $prevRequest->setWitel( Witel::find($registData['witel_id']) );
        $prevRequest->setLocations( RtuLocation::getByIds($registData['locations']) );
        $prevRequestText = $prevRequest->params->text;

        try {
    
            $request = static::request('TextDefault');
            $request->setText(function($text) use ($prevRequestText) {
                return $text->addText($prevRequestText)->newLine(2)
                    ->addText('Permintaan registrasi telah dibatalkan oleh user.');
            });

            $apprMsgs = $registData['approval_messages'];
            foreach($apprMsgs as $apprMsg) {
                if($apprMsg['chat_id'] != $chatId) {
                    $request->params->chatId = $apprMsg['chat_id'];
                    $request->params->messageId = $apprMsg['message_id'];
                    $request->sendUpdate();
                }
            }
    
        } catch(\Throwable $rr) {
            \MuhammadSabri1306\MyBotLogger\Entities\ErrorLogger::catch($err);
        }

    }
    Registration::delete($regist['id']);
    $hasCancelRegist = true;
}

if(!$telgUser['is_pic']) {
    
    if($hasCancelRegist) {
        $request = static::request('TextDefault');
        $request->setTarget( static::getRequestTarget() );
        $request->setText(function($text) {
            return $text->addText('Permintaan registrasi PIC telah dibatalkan.');
        });
        return $request->send();
    }

    $request = static::request('TextDefault');
    $request->setTarget( static::getRequestTarget() );
    $request->setText(function($text) {
        return $text->addText('Anda belum terdaftar sebagai PIC.');
    });
    return $request->send();

}

PicLocation::deleteByUserId($telgUser['id']);
AlertUsers::deleteByUserId($telgUser['id']);
TelegramUser::update($telgUser['id'], [ 'is_pic' => 0 ]);

$request = static::request('TextDefault');
$request->params->chatId = $chatId;
$request->setText(fn($text) => $text->addText('Status PIC anda telah di-reset.'));
return $request->send();