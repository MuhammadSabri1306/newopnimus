<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use App\Controller\Bot\RtuController;

class CekRtuCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'cekrtu';

    /**
     * @var string
     */
    protected $description = 'Check RTU detail command';

    /**
     * @var string
     */
    protected $usage = '/cekrtu';

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
        RtuController::$command = $this;
        return RtuController::checkRtu();
    }
}
