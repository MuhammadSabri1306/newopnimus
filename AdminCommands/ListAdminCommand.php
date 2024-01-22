<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use App\Controller\Bot\AdminController;

class ListAdminCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'listadmin';

    /**
     * @var string
     */
    protected $description = 'Show all admin users.';

    /**
     * @var string
     */
    protected $usage = '/listadmin';

    /**
     * @var string
     */
    protected $version = '0.1.0';

    /**
     * @var bool
     */
    protected $private_only = true;

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        AdminController::$command = $this;
        return AdminController::list();
    }
}