<?php

use App\Model\Registration;
use App\Model\Regional;
use App\Model\Witel;

$message = static::getMessage();
$chatId = $message->getChat()->getId();
$messageId = $message->getMessageId();

$response = static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();
if(!$registId) {
    return $response;
}

$regist = Registration::query(function($db, $table) use ($chatId) {
    $query = "SELECT * FROM $table WHERE request_type='user' AND status='unprocessed' AND chat_id=%i";
    $data = $db->queryFirstRow($query, $chatId);
    if(isset($data['data'])) $data['data'] = json_decode($data['data'], true);
    return $data ?? null;
});
if(!$regist) {
    return $response;
}

Registration::delete($registId);
$request = static::request('TextDefault');
$request->setTarget( static::getRequestTarget() );
$request->setText(fn($text) => $text->addText('Permintaan registrasi telah dibatalkan.'));
$response = $request->send();

$apprvMsgs = isset($regist['data']['approval_messages']) ? $regist['data']['approval_messages'] : [];
if(!is_array($apprvMsgs) || count($apprvMsgs) < 1) {
    return $response;
}

$prevRequest = static::request('Registration/SelectAdminApproval');
$prevRequest->setRegistrationData($regist);
if(in_array($regist['data']['level'], [ 'regional', 'witel' ])) {
    $regional = Regional::find($regist['data']['regional_id']);
    $prevRequest->setRegional($regional);
}
if($regist['data']['level'] == 'witel') {
    $witel = Witel::find($regist['data']['witel_id']);
    $prevRequest->setWitel($witel);
}
$prevRequestText = $prevRequest->params->text;

$request = static::request('TextDefault');
$request->setText(function($text) use ($prevRequestText) {
    return $text->addText($prevRequestText)
        ->newLine(2)->addItalic('Permintaan telah dibatalkan.');
});
$response = $request->send();

foreach($apprvMsgs as $apprvMsg) {
    $request->params->chatId = $apprvMsg['chat_id'];
    $request->params->messageId = $apprvMsg['message_id'];
    $request->send();
}

return $response;