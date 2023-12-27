<?php

error_reporting(E_ALL);
require __DIR__.'/../app/bootstrap.php';

try {
    
    // $dbConfig = App\Config\AppConfig::$DATABASE->default;
    // $db = new \MeekroDB($dbConfig->host, $dbConfig->username, $dbConfig->password, $dbConfig->name);
    $db = new \MeekroDB('10.62.170.140', 'admapp', 'weAREadmAPP!1!1', 'juan5684_opnimus_new');
    $username = $db->queryFirstColumn('SELECT username FROM telegram_user');
    dd($username);

} catch(\Throwable $err) {
    echo $err;
    dd($dbConfig);
}