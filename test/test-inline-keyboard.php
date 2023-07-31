<?php

require __DIR__.'/../app/bootstrap.php';
use App\Core\RequestData;
use Longman\TelegramBot\Entities\InlineKeyboard;

$req = New RequestData();

$req->parseMode = 'markdown';
$req->chatId = 222222;
$req->text = 'Sebelum mendaftar OPNIMUS apakah anda setuju dengan Ketentuan Penggunaan diatas?';
$req->replyMarkup = new InlineKeyboard([
    ['text' => '👍Core', 'url' => 'https://github.com/php-telegram-bot/core'],
    ['text' => '❌Example Bot', 'url' => 'https://github.com/php-telegram-bot/example-bot']
]);

// dd($reqData->chatId);
dd($req->build());