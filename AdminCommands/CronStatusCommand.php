<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use App\Controller\Bot\CronController;

class CronStatusCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'cronstatus';

    /**
     * @var string
     */
    protected $description = 'Check Alerting cron status.';

    /**
     * @var string
     */
    protected $usage = '/cronstatus';

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
        CronController::$command = $this;

        if(!CronController::isSuperAdmin()) {
            return Request::emptyResponse();
        }

        return CronController::nodeCronStatus();
    }
}
