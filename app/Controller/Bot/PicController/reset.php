<?php

use App\Core\CallbackData;

$message = static::getMessage();
$chatId = $message->getChat()->getId();

if(!$message->getChat()->isPrivateChat()) {
    
    $request = static::request('Error/TextErrorNotInPrivate');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

$conversation = static::getPicRegistConversation();
if($conversation->isExists()) {
    $conversation->cancel();
}

$telgUser = static::getUser();
if(!$telgUser) {

    $request = static::request('Error/TextUserUnidentified');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

if(!$telgUser['is_pic']) {

    $request = static::request('TextDefault');
    $request->setTarget( static::getRequestTarget() );
    $request->setText(fn($text) => $text->addText('Anda belum terdaftar sebagai PIC.'));
    return $request->send();

}

$request = static::request('Pic/SelectResetApproval');
$request->setTarget( static::getRequestTarget() );
$request->setLocations( $telgUser['locations'] );

$callbackData = new CallbackData('pic.resetapprv');
$request->setInKeyboard(function($inkeyboard) use ($callbackData) {
    $inkeyboard['continue']['callback_data'] = $callbackData->createEncodedData(1);
    $inkeyboard['cancel']['callback_data'] = $callbackData->createEncodedData(0);
    return $inkeyboard;
});

return $request->send();