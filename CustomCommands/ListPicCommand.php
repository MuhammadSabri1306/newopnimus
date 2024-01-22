<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use App\Controller\Bot\PicController;

class ListPicCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'listpic';

    /**
     * @var string
     */
    protected $description = 'List all pic in witel.';

    /**
     * @var string
     */
    protected $usage = '/listpic';

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
        PicController::$command = $this;
        return PicController::list();
    }
}
