<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use App\Controller\Bot\AlertController;

class RequestAlertCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'request_alert';

    /**
     * @var string
     */
    protected $description = 'Request Alert Group Exclusion.';

    /**
     * @var string
     */
    protected $usage = '/request_alert';

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
        AlertController::$command = $this;
        $conversation = AlertController::getAlertExclusionConversation();

        if(!$conversation->isExists()) {
            return AlertController::requestExclusion();
        }

        return AlertController::submitExclusionRequest();
    }
}
