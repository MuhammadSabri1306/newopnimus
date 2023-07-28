<?php

require __DIR__.'/../app/bootstrap.php';
use App\Core\RequestData;

$reqData = New RequestData();

$reqData->chatId = 12;
$reqData->parseMode = 'markdown';
$reqData->replyToMessageId = 14;
$reqData->animation = 'https://giphy.com/gifs/transformers-optimus-prime-transformer-transformers-rise-of-the-beasts-Bf3Anv7HuOPHEPkiOx';
$reqData->caption = 'test';

// dd($reqData->chatId);
dd($reqData->build());