<?php

use App\Core\CallbackData;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\TelegramAdmin;

$message = static::getMessage();
$messageId = $message->getMessageId();
$chatId = $message->getChat()->getId();

$admin = static::getAdmin();
if($admin && $admin['level'] == 'witel') {
    return static::createCallbackAnswer('Fitur ini tidak tersedia untuk Admin Witel', true, 10);
}

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();
if(!$admin) return static::sendEmptyResponse();

if($admin['level'] == 'nasional') {

    $request = static::request('Area/SelectRegional');
    $request->setTarget( static::getRequestTarget() );

    $regionalOptions = Regional::getSnameOrdered();
    if($admin['is_super_admin']) array_unshift($regionalOptions, [ 'id' => 'n', 'name' => 'NASIONAL' ]);
    $request->setRegionals($regionalOptions);

    $callbackData = new CallbackData('mngusr.rmadmintreg');
    $request->setInKeyboard(function($inKeyboardItem, $regional) use ($callbackData) {
        $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($regional['id']);
        return $inKeyboardItem;
    });

    return $request->send();

}

if($admin['level'] == 'regional') {

    $request = static::request('Area/SelectWitel');
    $request->setTarget( static::getRequestTarget() );
    $request->setWitels( Witel::getNameOrdered($admin['regional_id']) );

    $callbackData = new CallbackData('mngusr.rmadminwit');
    $request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
        $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
        return $inKeyboardItem;
    });

    return $request->send();

}