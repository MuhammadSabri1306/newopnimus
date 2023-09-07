<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

use App\Controller\Bot\UserController;
use App\Controller\Bot\PicController;

class GenericmessageCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'genericmessage';

    /**
     * @var string
     */
    protected $description = 'Handle generic message';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * Command execute method if MySQL is required but not available
     *
     * @return ServerResponse
     */
    public function executeNoDb(): ServerResponse
    {
        // Do nothing
        return Request::emptyResponse();
    }

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();

        // If a conversation is busy, execute the conversation command after handling the message.
        $conversation = new Conversation(
            $message->getFrom()->getId(),
            $message->getChat()->getId()
        );

        // Fetch conversation command if it exists and execute it.
        if ($conversation->exists() && $command = $conversation->getCommand()) {
            return $this->telegram->executeCommand($command);
        }

        $command =$this->getConversationCommand();
        if($command != '') {
            return $this->telegram->executeCommand($command);
        }

        return Request::emptyResponse();
    }

    private function getConversationCommand()
    {
        // App\Core\Conversation
        UserController::$command = $this;
        $registConversation = UserController::getRegistConversation();
        if($registConversation->isExists()) {
            return 'start';
        }

        PicController::$command = $this;
        $registPicConversation = PicController::getPicRegistConversation();
        if($registPicConversation->isExists()) {
            return 'setpic';
        }

        return '';
    }
}
