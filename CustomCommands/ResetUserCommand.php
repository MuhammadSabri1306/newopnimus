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
 * User "/survey" command
 *
 * Example of the Conversation functionality in form of a simple survey.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use App\Controller\Bot\UserController;

class ResetUserCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'resetuser';

    /**
     * @var string
     */
    protected $description = 'Reset user\'s registration.';

    /**
     * @var string
     */
    protected $usage = '/resetuser';

    /**
     * @var string
     */
    protected $version = '0.4.0';

    /**
     * @var bool
     */
    protected $private_only = false;

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        UserController::$command = $this;
        return UserController::resetRegistration();
    }
}
