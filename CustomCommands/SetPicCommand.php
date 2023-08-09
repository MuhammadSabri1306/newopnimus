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

class SetPicCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'setpic';

    /**
     * @var string
     */
    protected $description = 'Set pic location.';

    /**
     * @var string
     */
    protected $usage = '/setpic';

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
        PicController::$command = $this;
        return PicController::setLocations();
    }
}
