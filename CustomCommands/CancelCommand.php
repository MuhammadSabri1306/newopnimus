<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

use App\Controller\BotController;
use App\Controller\Bot\UserController;
use App\Controller\Bot\PicController;

class CancelCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'cancel';

    /**
     * @var string
     */
    protected $description = 'Cancel the currently active conversation';

    /**
     * @var string
     */
    protected $usage = '/cancel';

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
        BotController::$command = $this;

        UserController::whenRegistCancel();
        PicController::whenRegistCancel();

        return BotController::sendEmptyResponse();
    }
}
