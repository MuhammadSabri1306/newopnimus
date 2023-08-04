<?php
require __DIR__.'/../app/bootstrap.php';

// use App\Model\TelegramUser;
use App\Model\Witel;

// $isUserExists = TelegramUser::exists('1231231');
// dd($isUserExists);

$witels = Witel::getNameOrdered('2');
dd($witels);