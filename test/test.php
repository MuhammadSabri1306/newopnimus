<?php

require __DIR__.'/../app/bootstrap.php';
use App\Core\Conversation;
use App\Model\TelegramUser;
use App\Controller\BotController;

$chatId = 1931357638;
$telgUser = TelegramUser::findByChatId($chatId);
$conversation = new Conversation('regist_pic', $chatId, $chatId);

BotController::getRequest('Area/SelectLocation', [ $chatId, $conversation->witelId ]);