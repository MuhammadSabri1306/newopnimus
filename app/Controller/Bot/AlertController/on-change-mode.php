<?php

use App\Model\AlertModes;
use App\Model\AlertUsers;

$message = static::getMessage();
$messageId = $message->getMessageId();
$chatId = $message->getChat()->getId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

AlertUsers::useDefaultJoinPattern();
$user = AlertUsers::findByChatId($chatId);
$mode = AlertModes::find($modeId);

if(!$mode) {
    throw new \Error("Alert Mode id:$modeId is not exists");
} elseif(!$user) {
    throw new \Error("User with chat_id:$chatId is not exists");
}

AlertUsers::update($user['alert_user_id'], [ 'mode_id' => $modeId ]);
$modeTitle = $mode['title'];

$request = static::request('TextDefault');
$request->setTarget( static::getRequestTarget() );
$request->setText(function($text) use ($modeTitle) {
    return $text->addText('Mode Alerting telah diubah ke ')
        ->addBold($modeTitle)
        ->addText('.');
});

return $request->send();