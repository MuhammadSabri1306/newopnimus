<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use App\Controller\Bot\StatisticController;

class StatHarianCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'statharian';

    /**
     * @var string
     */
    protected $description = 'Get alarm port and rtu statistic on current day';

    /**
     * @var string
     */
    protected $usage = '/statharian';

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
        StatisticController::$command = $this;
        return StatisticController::daily();
    }
}
