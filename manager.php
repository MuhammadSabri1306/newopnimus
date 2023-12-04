<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

/**
 * This file is part of the PHP Telegram Bot example-bot package.
 * https://github.com/php-telegram-bot/example-bot/
 *
 * (c) PHP Telegram Bot Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This configuration file is used as the main script for the PHP Telegram Bot Manager.
 *
 * For the full list of options, go to:
 * https://github.com/php-telegram-bot/telegram-bot-manager#set-extra-bot-parameters
 */

try {

    require_once __DIR__.'/app/bootstrap.php';
    $config = \App\Config\BotConfig::buildArray();

    \MuhammadSabri1306\MyBotLogger\Logger::$botToken = \App\Config\BotConfig::$BOT_TOKEN;
    \MuhammadSabri1306\MyBotLogger\Logger::$botUsername = \App\Config\BotConfig::$BOT_USERNAME;
    \MuhammadSabri1306\MyBotLogger\Logger::$chatId = \App\Config\AppConfig::$LOG_CHAT_ID;

    // $config = \App\Config\BotConfig::buildArray();
    $bot = new TelegramBot\TelegramBotManager\BotManager($config);

    // testHook(['manager.php', $config['commands']['paths']]);
    // Run the bot!
    $bot->run();

    // Handling error response from all controllers
    // $telegramResponse = $bot->getTelegram()->getLastCommandResponse();
    // if($telegramResponse && $telegramResponse instanceof \Longman\TelegramBot\Entities\ServerResponse) {
    //     if(!$telegramResponse->isOk()) {
    //         throw new \App\Core\Exception\TelegramResponseException($telegramResponse);
    //     }
    // }

} catch(\Throwable $err) {

    echo $err;
    $chatIdExists = true;
    if($err instanceof \App\Core\Exception\TelegramResponseException) {

        \MuhammadSabri1306\MyBotLogger\Entities\TelegramResponseLogger::catch($err);
        if($err->getResponseData()['description'] == 'Bad Request: chat not found') {
            $chatIdExists = false;
        }

    } else {

        \MuhammadSabri1306\MyBotLogger\Entities\ErrorLogger::catch($err);

    }

    if($chatIdExists) {
        \App\Controller\BotController::sendErrorMessage();
    }

}