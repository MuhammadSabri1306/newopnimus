<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use App\Core\DB;
use App\Controller\BotController;
// use App\Core\Logger;

class TestCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'test';

    /**
     * @var string
     */
    protected $description = 'Test command';

    /**
     * @var string
     */
    protected $usage = '/test';

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
        $message = $this->getMessage();
        $db = new DB();
        $user = $db->queryFirstRow('SELECT username FROM telegram_user WHERE chat_id=%i', $message->getChat()->getId());
        $username = $user['username'];

        // return $this->replyToChat("Test: $username");
        return BotController::sendDebugMessage('test');
    }
}
