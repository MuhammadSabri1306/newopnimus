<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use App\Controller\Bot\PicController;

class ResetPicCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'resetpic';

    /**
     * @var string
     */
    protected $description = 'Reset user back to normal user.';

    /**
     * @var string
     */
    protected $usage = '/resetpic';

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
        PicController::$command = $this;
        return PicController::reset();
    }
}
