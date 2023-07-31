<?php

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

require __DIR__.'/app/bootstrap.php';

try {
    $bot = new TelegramBot\TelegramBotManager\BotManager($config);
    
    // Run the bot!
    $bot->run();
} catch(\Exception $e) {
    echo $e;
}
// } catch (Longman\TelegramBot\Exception\TelegramException $e) {
//     // Uncomment this to output any errors (ONLY FOR DEVELOPMENT!)
//     echo $e;
// } catch (Longman\TelegramBot\Exception\TelegramLogException $e) {
//     // Uncomment this to output log initialisation errors (ONLY FOR DEVELOPMENT!)
//     echo $e;
// } catch(TelegramBot\TelegramBotManager\Exception\InvalidAccessException $e) {
//     echo $e;
// }
