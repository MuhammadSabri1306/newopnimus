<?php

$conversation = static::getPicRegistConversation();
if(!$conversation->isExists()) {
    return static::sendEmptyResponse();
}

$conversation->cancel();

$request = static::request('TextDefault');
$request->setTarget( static::getRequestTarget() );
$request->setText('Registrasi PIC dibatalkan.');
return $request->send();