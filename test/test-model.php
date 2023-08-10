<?php
require __DIR__.'/../app/bootstrap.php';

use App\Model\TelegramPersonalUser;

$dataPersonal = [
    'user_id' => '11',
    'nama' => 'Muhammad Sabri',
    'telp' => '+6285144392944',
    'instansi' => 'Test Instansi',
    'unit' => 'Test Unit',
    'is_organik' => false,
    'nik' => '123456',
];

$personalUser = TelegramPersonalUser::create($dataPersonal);

dd($personalUser);