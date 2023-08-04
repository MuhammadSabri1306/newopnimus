<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Conversation;
use App\Controller\BotController;
use App\Controller\Bot\UserController;

class CallbackqueryCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'callbackquery';

    /**
     * @var string
     */
    protected $description = 'Handle the callback query';

    /**
     * @var string
     */
    protected $version = '1.2.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    
    /**
     * @var bool
     */
    protected $private_only = false;

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws \Exception
     */
    public function execute(): ServerResponse
    {
        $callbackQuery = $this->getCallbackQuery();
        $callbackData  = $callbackQuery->getData();

        UserController::$command = $this;
        if(BotController::catchCallback(UserController::class, $callbackData, $callbackQuery)) {
            return $callbackQuery->answer();
        }

        return $callbackQuery->answer([
            'text'       => 'Content of the callback data: ' . $callbackData,
            'show_alert' => true,
            'cache_time' => 10,
        ]);
    }
}