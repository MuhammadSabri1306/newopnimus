<?php

use MuhammadSabri1306\MyBotLogger\Entities\ErrorWithDataLogger;
use App\Model\Registration;

$regist = Registration::find($registId);
if(!$regist) {
    
    try {
        throw new \Error('$regist not found');
    } catch(\Throwable $err) {
        ErrorWithDataLogger::catch($err, [ 'registId' => $registId ]);
    }

    $request = static::request('Error/TextErrorServer');
    $request->setTarget( static::getRequestTarget() );
    return $request->send();

}

$request = static::request('Registration/TextUserRejected');
$request->setRejectedDate($regist['updated_at']);
$request->params->chatId = $regist['chat_id'];
if( isset($regist['data']['message_thread_id']) ) {
    $request->params->messageThreadId = $telgUser['message_thread_id'];
}
return $request->send();