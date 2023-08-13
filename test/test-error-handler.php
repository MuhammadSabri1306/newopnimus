<?php
require __DIR__.'/../app/bootstrap.php';

useHelper('error-handler');

try {
    echo $test;
} catch(\Exception $err) {
    return sendTelegramMessageError($err, 1931357638);
}