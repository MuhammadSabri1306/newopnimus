<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use App\Controller\Bot\PortController;

class CekPueCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'cekpue';

    /**
     * @var string
     */
    protected $description = 'Check PUE PORT value command';

    /**
     * @var string
     */
    protected $usage = '/cekpue';

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
        return PortController::checkPue();
    }
}
