<?php
require __DIR__.'/../app/bootstrap.php';

use App\Model\Registration;
use App\BuiltMessageText\AdminText;

$registData = Registration::find(5);
dd(AdminText::getUserApprovalText($registData));