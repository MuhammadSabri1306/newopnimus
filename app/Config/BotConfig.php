<?php
namespace App\Config;

use Longman\TelegramBot\Entities\Update;
use App\Helper\Helper;

class BotConfig
{
    public static $BOT_TOKEN = null;
    public static $BOT_USERNAME = null;
    public static $HOOK_URL = null;
    public static $PRIVATE_KEY = null;

    public static $MYSQL_HOST = null;
    public static $MYSQL_PORT = null;
    public static $MYSQL_USERNAME = null;
    public static $MYSQL_PASSWORD = null;
    public static $MYSQL_DATABASE = null;
    public static $MYSQL_TABLE_PREFIX = null;

    public static function initConfig()
    {
        static::$BOT_TOKEN = Helper::env('BOT_TOKEN');
        static::$BOT_USERNAME = Helper::env('BOT_USERNAME');
        static::$HOOK_URL = Helper::env('BOT_HOOK_URL');
        static::$PRIVATE_KEY = Helper::env('BOT_PRIVATE_KEY');
        static::$MYSQL_HOST = Helper::env('MYSQL_BOT_HOST');
        static::$MYSQL_PORT = Helper::env('MYSQL_BOT_PORT');
        static::$MYSQL_USERNAME = Helper::env('MYSQL_BOT_USERNAME');
        static::$MYSQL_PASSWORD = Helper::env('MYSQL_BOT_PASSWORD');
        static::$MYSQL_DATABASE = Helper::env('MYSQL_BOT_DATABASE');
        static::$MYSQL_TABLE_PREFIX = Helper::env('MYSQL_BOT_PREFIX');
    }

    public static function buildArray()
    {
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
         * This file contains all the configuration options for the PHP Telegram Bot.
         *
         * It is based on the configuration array of the PHP Telegram Bot Manager project.
         *
         * Simply adjust all the values that you need and extend where necessary.
         *
         * Options marked as [Manager Only] are only required if you use `manager.php`.
         *
         * For a full list of all options, check the Manager Readme:
         * https://github.com/php-telegram-bot/telegram-bot-manager#set-extra-bot-parameters
         */

        $mysqlConfig = [
            'host' => static::$MYSQL_HOST,
            'user' => static::$MYSQL_USERNAME,
            'password' => static::$MYSQL_PASSWORD,
            'database' => static::$MYSQL_DATABASE,
        ];

        // if(static::$MYSQL_PORT) {
        //     $mysqlConfig['port'] = static::$MYSQL_PORT;
        // }

        if(static::$MYSQL_TABLE_PREFIX) {
            $mysqlConfig['table_prefix'] = static::$MYSQL_TABLE_PREFIX;
        }

        return [

            // Add you bot's API key and name
            'api_key' => static::$BOT_TOKEN,
            'bot_username' => static::$BOT_USERNAME, // Without "@"

            // [Manager Only] Secret key required to access the webhook
            'secret' => static::$PRIVATE_KEY,

            // When using the getUpdates method, this can be commented out
            'webhook' => [
                'url' => static::$HOOK_URL,
                // Use self-signed certificate
                // 'certificate'     => __DIR__ . '/path/to/your/certificate.crt',
                // Limit maximum number of connections
                // 'max_connections' => 5,
                // 'allowed_updates' => ['message', 'edited_channel_post', 'callback_query'],
                'allowed_updates' => [
                    Update::TYPE_MESSAGE,
                    Update::TYPE_EDITED_MESSAGE,
                    Update::TYPE_CALLBACK_QUERY,
                    Update::TYPE_INLINE_QUERY,
                    Update::TYPE_CHAT_MEMBER,
                ],
            ],

            // All command related configs go here
            'commands' => [

                // Define all paths for your custom commands
                // DO NOT PUT THE COMMAND FOLDER THERE. IT WILL NOT WORK. 
                // Copy each needed Commandfile into the CustomCommand folder and uncommend the Line 49 below
                'paths'   => [
                    __DIR__ . '/../../CustomCommands',
                    __DIR__ . '/../../AdminCommands',
                ],

                // Here you can set any command-specific parameters
                'configs' => [
                    // - Google geocode/timezone API key for /date command (see DateCommand.php)
                    // 'date'    => ['google_api_key' => 'your_google_api_key_here'],
                    // - OpenWeatherMap.org API key for /weather command (see WeatherCommand.php)
                    // 'weather' => ['owm_api_key' => 'your_owm_api_key_here'],
                    // - Payment Provider Token for /payment command (see Payments/PaymentCommand.php)
                    // 'payment' => ['payment_provider_token' => 'your_payment_provider_token_here'],
                ],

            ],

            // Define all IDs of admin users
            'admins' => [
                // 123,
            ],

            // Enter your MySQL database credentials
            'mysql' => $mysqlConfig,

            // Logging (Debug, Error and Raw Updates)
            'logging'  => [
                'debug'  => __DIR__ . '/logs/bot-debug.log',
                'error'  => __DIR__ . '/logs/bot-error.log',
                'update' => __DIR__ . '/logs/bot-update.log',
            ],

            // Set custom Upload and Download paths
            'paths'        => [
                'download' => __DIR__ . '/Download',
                'upload'   => __DIR__ . '/Upload',
            ],

            // Requests Limiter (tries to prevent reaching Telegram API limits)
            'limiter'      => [
                'enabled' => true,
            ],

            // ================== CUSTOM ==================

            'validate_request' => false

            // (array) When using `validate_request`, also allow these IPs.
            // 'valid_ips'    => [
            //     '10.62.26.2', // juarayya,
            //     '182.1.208.68', // mirror juarayya
            // ],

            // (string) Override the custom input of your bot (mostly for testing purposes!).
            // 'custom_input'     => '{"some":"raw", "json":"update"}'
        ];
    }
}

\App\Config\BotConfig::initConfig();