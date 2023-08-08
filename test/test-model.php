<?php
require __DIR__.'/../app/bootstrap.php';

// use App\Model\TelegramUser;
// use App\Model\Witel;
use App\Model\Registration;
use App\Model\TelegramAdmin;
use App\BuiltMessageText\AdminText;

// $isUserExists = TelegramUser::exists('1231231');
// dd($isUserExists);

// $witels = Witel::getNameOrdered('2');
// $witel = Witel::find(null);

$registData = Registration::find(15);
$admins = TelegramAdmin::getByUserArea($registData);

dd(AdminText::getUserApprovalText($registData)->get());

dd($admins);