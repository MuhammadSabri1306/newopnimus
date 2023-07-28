<?php
require __DIR__.'/../app/bootstrap.php';

use Monolog\Logger;

try {
    Longman\TelegramBot\TelegramLog::initialize(
        new Monolog\Logger('telegram_bot', [
            (new Monolog\Handler\StreamHandler($config['logging']['debug'], Monolog\Logger::DEBUG))->setFormatter(new Monolog\Formatter\LineFormatter(null, null, true)),
            (new Monolog\Handler\StreamHandler($config['logging']['error'], Monolog\Logger::ERROR))->setFormatter(new Monolog\Formatter\LineFormatter(null, null, true)),
        ]),
        $loggerInfo
     );
     
    TelegramLog::debug('This is a debug message');
} catch(Exception $e) {
    var_dump($e);
}