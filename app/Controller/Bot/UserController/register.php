<?php

use App\Core\CallbackData;

$conversation = static::getRegistConversation();
if(!$conversation->isExists()) {

    $response = static::checkRegistStatus();
    if($response) {
        return $response;
    }

    return static::tou();

}

$message = static::getMessage();
$isPrivateChat = $message->getChat()->isPrivateChat();
$chatId = $message->getChat()->getId();
$fromId = static::getFrom()->getId();

if($conversation->getStep() == 0) {
    return static::setRegistLevel();
}

$messageText = trim($message->getText(true));

if($conversation->getStep() == 1) {

    if(empty($messageText)) {
        $request = static::request('TextDefault');
        $request->setTarget( static::getRequestTarget() );
        if($isPrivateChat) {
            $request->setText(fn($text) => $text->addText('Silahkan ketikkan nama lengkap anda.'));
        } else {
            $request->setText(fn($text) => $text->addText('Silahkan ketikkan deskripsi grup.'));
        }
        return $request->send();
    }

    if($isPrivateChat) {
        $conversation->fullName = $messageText;
    } else {
        $conversation->groupDescription = $messageText;
    }

    $conversation->nextStep();
    $conversation->commit();
    $messageText = '';

}

if(!$isPrivateChat && $conversation->getStep() > 1) {
    if($conversation->getStep() == 2) {
        return static::submitRegistration();
    }
    return static::sendEmptyResponse();
}

if($conversation->getStep() == 2) {

    $contact = $message->getContact();
    if(!$contact) {
        $request = static::request('Registration/ShareContact');
        $request->setTarget( static::getRequestTarget() );
        return $request->send();
    }

    $conversation->telp = $contact->getPhoneNumber();
    $conversation->nextStep();
    $conversation->commit();
    $messageText = '';

}

if($conversation->getStep() == 3) {

    if(empty($messageText)) {
        $request = static::request('TextDefault');
        $request->setTarget( static::getRequestTarget() );
        $request->setText(fn($text) => $text->addText('Silahkan ketikkan instansi anda.'));
        return $request->send();
    }
    
    $conversation->instansi = $messageText;
    $conversation->nextStep();
    $conversation->commit();
    $messageText = '';

}

if($conversation->getStep() == 4) {

    if(empty($messageText)) {
        $request = static::request('TextDefault');
        $request->setTarget( static::getRequestTarget() );
        $request->setText(fn($text) => $text->addText('Silahkan ketikkan unit kerja anda.'));
        return $request->send();
    }
    
    $conversation->unit = $messageText;
    $conversation->nextStep();
    $conversation->commit();
    $messageText = '';

}

if($conversation->getStep() == 5) {

    $request = static::request('Registration/SelectIsOrganik');
    $request->setTarget( static::getRequestTarget() );
    
    $callbackData = new CallbackData('user.orgn');
    $callbackData->limitAccess($fromId);
    $request->setInKeyboard(function($inKeyboardData) use ($callbackData) {
        $inKeyboardData['yes']['callback_data'] = $callbackData->createEncodedData(1);
        $inKeyboardData['no']['callback_data'] = $callbackData->createEncodedData(0);
        return $inKeyboardData;
    });
    
    return $request->send();

}

if($conversation->getStep() == 6) {

    if(empty($messageText)) {
        $request = static::request('TextDefault');
        $request->setTarget( static::getRequestTarget() );
        $request->setText(fn($text) => $text->addText('Silahkan ketikkan NIK anda.'));
        return $request->send();
    }
    
    $conversation->nik = $messageText;
    $conversation->nextStep();
    $conversation->commit();
    $messageText = '';

}

if($conversation->getStep() == 7) {
    return static::submitRegistration();
}
return static::sendEmptyResponse();