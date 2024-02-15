<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use App\Controller\Bot\ManagementUserController;

class UserManagementCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'user_management';

    /**
     * @var string
     */
    protected $description = 'Manage users';

    /**
     * @var string
     */
    protected $usage = '/user_management';

    /**
     * @var string
     */
    protected $version = '1.0.0';

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
        ManagementUserController::$command = $this;
        return ManagementUserController::menu();
    }
}