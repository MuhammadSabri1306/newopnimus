<?php
namespace App\Core;

class Logger
{
    public static function debug()
    {
        // $telegram = new \Telegram\Bot\Api('YOUR_BOT_TOKEN');
        global $telegram;

        $logger = new \Telegram\Bot\LoggerLogger();
        $logger->setPath(__DIR__ . '/../../logs');
        $logger->setLevel(\Telegram\Bot\Logger::DEBUG); // You can set the desired log level (DEBUG, INFO, WARNING, ERROR)
        $telegram->setLogger($logger);

        return $logger;
    }
}