<?php
require __DIR__.'/../app/bootstrap.php';

use App\Model\TelegramUser;

$isUserExists = TelegramUser::exists('1231231');
dd($isUserExists);