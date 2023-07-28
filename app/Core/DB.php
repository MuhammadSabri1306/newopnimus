<?php
namespace App\Core;

use MeekroDB;

class DB extends MeekroDB
{
    public function __construct()
    {
        $host = '10.60.164.18';
        $user = 'admindb';
        $password = '@Dm1ndb#2020';
        $dbName = 'juan5684_opnimus_new';
        parent::__construct($host, $user, $password, $dbName);
    }
}