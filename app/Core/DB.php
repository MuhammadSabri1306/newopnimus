<?php
namespace App\Core;

use MeekroDB;

class DB extends MeekroDB
{
    public function __construct()
    {
        // $host = '10.60.164.18';
        // $user = 'admindb';
        // $password = '@Dm1ndb#2020';
        // $dbName = 'juan5684_opnimus_new';
        $host = '10.62.175.4';
        $user = 'admapp';
        $password = '4dm1N4Pp5!!';
        $dbName = 'juan5684_opnimus_new';
        parent::__construct($host, $user, $password, $dbName);
    }
}