<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use App\Controller\Bot\StatisticController;

class StatBulananCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'statbulanan';

    /**
     * @var string
     */
    protected $description = 'Get alarm port and rtu statistic on current month';

    /**
     * @var string
     */
    protected $usage = '/statbulanan';

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
        return StatisticController::monthly();
    }
}
