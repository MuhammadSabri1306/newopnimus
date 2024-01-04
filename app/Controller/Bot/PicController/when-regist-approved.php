<?php

use App\Model\TelegramUser;

$telgUser = TelegramUser::findByRegistId($registId);
if(!$telgUser) {
    return static::sendEmptyResponse();
}

$request = static::request('Registration/TextPicApproved');
$request->params->chatId = $telgUser['chat_id'];
$request->setUser($telgUser);
return $request->send();