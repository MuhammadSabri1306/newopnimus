<?php

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);     
});


use Longman\TelegramBot\Request;

function sendTelegramMessageError($err, $chatId) {
    return Request::sendMessage([
        'chat_id' => $chatId,
        'text' => $err->getMessage()
    ]);
}