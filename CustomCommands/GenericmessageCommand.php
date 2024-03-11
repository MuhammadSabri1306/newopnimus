<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

use App\Controller\BotController;
use App\Controller\Bot\UserController;
use App\Controller\Bot\PicController;
use App\Controller\Bot\AlertController;
use App\Controller\Bot\AdminController;

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
        $chatId = $message->getChat()->getId();
        $userId = $message->getFrom()->getId();

        $activeConv = \App\Model\Conversation::findActive($chatId, $userId);
        $command = $activeConv ? $this->getConversationCommand($activeConv) : null;
        if($command) {
            return $this->telegram->executeCommand($command);
        }

        return Request::emptyResponse();
    }

    private function getConversationCommand($activeConversation)
    {
        $conversationList = [
            'regist' => 'start',
            'regist_pic' => 'setpic',
            'regist_admin' => 'request_admin',
            'alert_exclusion' => 'request_alert',
            'admin_rm_user' => 'user_management',
            'admin_rm_pic' => 'user_management',
            'admin_rm_admin' => 'user_management',
        ];

        foreach($conversationList as $conversationName => $command) {
            if($conversationName == $activeConversation['name']) {
                return $command;
            }
        }

        return null;
    }
}
