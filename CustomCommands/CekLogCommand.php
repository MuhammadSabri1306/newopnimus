<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use App\Controller\Bot\PortController;

class CekLogCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'ceklog';

    /**
     * @var string
     */
    protected $description = 'Check RTU PORT history command';

    /**
     * @var string
     */
    protected $usage = '/ceklog';

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
        PortController::$command = $this;
        return PortController::checkLog();
    }
}
