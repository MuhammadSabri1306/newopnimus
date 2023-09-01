<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use App\Controller\Bot\PortController;

class CekPortCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'cekport';

    /**
     * @var string
     */
    protected $description = 'Check RTU PORT detail command';

    /**
     * @var string
     */
    protected $usage = '/cekport';

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
        PortController::$command = $this;
        return PortController::checkPort();
    }
}
