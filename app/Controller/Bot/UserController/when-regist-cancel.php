<?php

use App\Core\CallbackData;
use App\Model\Registration;

$chatId = static::getMessage()->getChat()->getId();
$fromId = static::getFrom()->getId();
$regist = Registration::query(function($db, $table) use ($chatId) {
    $query = "SELECT * FROM $table WHERE request_type='user' AND status='unprocessed' AND chat_id=%i";
    $data = $db->queryFirstRow($query, $chatId);
    if(isset($data['data'])) $data['data'] = json_decode($data['data'], true);
    return $data ?? null;
});

$response = null;
if($regist) {

    $request = static::request('Registration/SelectOnReviewCancel');
    $request->setTarget( static::getRequestTarget() );

    $request->setRegistration($regist);
    if($regist['data']['level'] == 'regional' || $regist['data']['level'] == 'witel') {
        $request->setRegional( Regional::find($regist['data']['regional_id']) );
    }
    if($regist['data']['level'] == 'witel') {
        $request->setWitel( Witel::find($regist['data']['witel_id']) );
    }

    $callbackData = new CallbackData('user.regcancel');
    $callbackData->limitAccess($fromId);
    $registId = $regist['id'];
    $request->setInKeyboard(function($inKeyboard) use ($callbackData, $registId) {
        $inKeyboard['yes']['callback_data'] = $callbackData->createEncodedData($registId);
        $inKeyboard['no']['callback_data'] = $callbackData->createEncodedData(0);
        return $inKeyboard;
    });
    $response = $request->send();

}

$conversation = static::getRegistConversation();
if($conversation->isExists()) {

    $conversation->cancel();
    if(!$response) {
        $request = static::request('TextDefault');
        $request->setTarget( static::getRequestTarget() );
        $request->setText(fn($text) => $text->addText('Registrasi dibatalkan.'));
        $response = $request->send();
    }

}

return $response;