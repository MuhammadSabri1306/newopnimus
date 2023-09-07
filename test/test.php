<?php

require __DIR__.'/../app/bootstrap.php';
use App\Controller\BotController;
use App\Model\TelegramUser;
use App\BuiltMessageText\PicText;

// $chatId = 55510658;
$chatId = 1931357638;
$telgUser = TelegramUser::findByChatId($chatId);
$text = PicText::getRegistApprovedText($telgUser)->get();
dd($text);