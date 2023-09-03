<?php

require __DIR__.'/../app/bootstrap.php';
use App\Core\Conversation;
use App\Model\Registration;
use App\Model\TelegramAdmin;
use App\Controller\Bot\AdminController;
use App\BuiltMessageText\AdminText;

$registId = '16';
$registData = Registration::find($registId);
$admins = TelegramAdmin::getByUserArea($registData['data']);
dd_json($admins);

// dd_json([
//     'registStatus' => $registStatus
// ]);