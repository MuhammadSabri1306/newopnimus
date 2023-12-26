<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use App\Controller\Bot\SyncLocationController;

class SyncLocCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'syncloc';

    /**
     * @var string
     */
    protected $description = 'Sync NewOsase API location.';

    /**
     * @var string
     */
    protected $usage = '/syncloc';

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
        SyncLocationController::$command = $this;

        if(!SyncLocationController::isSuperAdmin()) {
            return Request::emptyResponse();
        }

        return SyncLocationController::sync();
    }
}
