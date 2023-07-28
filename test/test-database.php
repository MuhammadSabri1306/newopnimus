<?php
require __DIR__.'/../app/bootstrap.php';

use App\Core\Logger;
use App\Core\DB;

$db = new DB();
$username = $db->queryFirstColumn('SELECT username FROM telegram_user');
dd($username);