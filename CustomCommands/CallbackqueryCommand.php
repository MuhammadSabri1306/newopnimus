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
 * Callback query command
 *
 * This command handles all callback queries sent via inline keyboard buttons.
 *
 * @see InlinekeyboardCommand.php
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Controller\Bot\UserController;

class CallbackqueryCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'callbackquery';

    /**
     * @var string
     */
    protected $description = 'Handle the callback query';

    /**
     * @var string
     */
    protected $version = '1.2.0';

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws \Exception
     */
    public function execute(): ServerResponse
    {
        $callbackQuery = $this->getCallbackQuery();
        $callbackData  = $callbackQuery->getData();

        if($callbackData == UserController::$cdRegistStart) {
            return $callbackQuery->answer([
                'text'       => 'Start Regist',
                'show_alert' => true,
                'cache_time' => 5,
            ]);
        }

        if($callbackData === UserController::$cdRegistCancel) {
            UserController::$command = $this;
            UserController::registCancel();
            return $callbackQuery->answer();
        }

        return $callbackQuery->answer([
            'text'       => 'Content of the callback data: ' . $callbackData,
            'show_alert' => true, // Randomly show (or not) as an alert.
            'cache_time' => 10,
        ]);
    }
}
