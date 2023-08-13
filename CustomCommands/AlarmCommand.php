<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\ChatAction;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use App\Controller\Bot\AlarmController;

class AlarmCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'alarm';

    /**
     * @var string
     */
    protected $description = 'Get all alarm in area';

    /**
     * @var string
     */
    protected $usage = '/alarm';

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
        AlarmController::$command = $this;
        $response = AlarmController::checkExistAlarm();
        return $response;
    }
}