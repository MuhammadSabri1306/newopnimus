<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use App\Controller\Bot\AdminController;

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
        $chatId = $this->getMessage()->getChat()->getId();
        if($chatId != '1931357638') {
            return Request::sendMessage([
                'chat_id' => $chatId,
                'parse_mode' => 'markdown',
                'text' => 'TEST command'.PHP_EOL.'___- Developer only___'
            ]);
        }
        
        return $this->replyToChat('Test');
    }
}
