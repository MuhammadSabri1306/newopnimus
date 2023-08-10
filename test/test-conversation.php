<?php
require __DIR__.'/../app/bootstrap.php';

use App\Core\Conversation;

$conversation = new Conversation('regist', 1931357638, 1931357638);

// if(!$conversation->isExists()) {
//     $conversation->create();
// }

header('Content-Type: application/json; charset=utf-8');
// echo $conversation->toJson();

// $conversation->name = "Muhammad Sabri";
// $conversation->nextStep();
// $conversation->commit();
// dd($conversation->getStep());

echo $conversation->toJson();