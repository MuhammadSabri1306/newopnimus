<?php

require __DIR__.'/../app/bootstrap.php';
use App\Core\Conversation;
use App\Model\Registration;
use App\Model\TelegramAdmin;
use App\Controller\Bot\AdminController;
use App\BuiltMessageText\AdminText;

$registId = '9';
$userId = '1931357638';
$chatId = '1931357638';

$conversation = new Conversation('regist', $userId, $chatId);
$conversation->setUserId($userId);
extract(AdminController::getRegistData($conversation->registId));

$reqData = AdminController::getUnavailableApproveText($registData);
$reqData->chatId = $chatId;

dd_json([
    'status' => $status,
    'registData' => $registData,
]);