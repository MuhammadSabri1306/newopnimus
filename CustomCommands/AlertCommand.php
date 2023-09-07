<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use App\Controller\Bot\AlertController;

class AlertCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'alert';

    /**
     * @var string
     */
    protected $description = 'Switch ON/OFF alert.';

    /**
     * @var string
     */
    protected $usage = '/alert';

    /**
     * @var string
     */
    protected $version = '0.1.0';

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
        AlertController::$command = $this;
        return AlertController::switch();
    }
}
