<?php
namespace App\Test;

error_reporting(E_ALL);
require __DIR__.'/../app/bootstrap.php';

use MeekroDB;
class DB extends MeekroDB
{
    public function __construct()
    {
        $host = '10.62.170.140';
        $user = 'admapp';
        $password = 'weAREadmAPP!1!1';
        $dbName = 'juan5684_opnimus_new';
        $port = 3306;

        parent::__construct($host, $user, $password, $dbName, $port);
    }
}

try {
    
    $db = new DB();
    $username = $db->queryFirstColumn('SELECT username FROM telegram_user');
    dd($username);

} catch(\Throwable $err) {
    echo $err;
}