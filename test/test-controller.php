<?php
require __DIR__.'/../app/bootstrap.php';

use App\Core\Controller;
use App\Controller\Bot\UserController;

$result = Controller::run(UserController::class, 'isRegisted', ['chatId' => '-978347278']);
dd($result);