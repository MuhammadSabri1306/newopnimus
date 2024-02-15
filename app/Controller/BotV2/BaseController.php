<?php
namespace App\Controller\BotV2;

use Longman\TelegramBot\Commands\Command;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Message;
use App\Core\Controller;
use App\Core\Conversation;
use App\Core\RequestData;
use App\Model\TelegramUser;

class BaseController extends Controller
{
    protected $command;
    private $isCmdCallback;
    private $cmdMessage;
    private $cmdFrom;
    private $currUser;
    private $reqTarget;

    protected $callbacks = [];

    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    public function isCallback(): bool
    {
        if(!isset($this->isCmdCallback)) {
            $this->isCmdCallback = method_exists($this->command, 'getCallbackQuery');
        }
        return $this->isCmdCallback;
    }

    public function getMessage(): Message
    {
        if(!isset($this->cmdMessage)) {
            if($this->isCallback()) {
                $this->cmdMessage = $this->command->getCallbackQuery()->getMessage();
            } else {
                $this->cmdMessage = $this->command->getMessage();
            }
        }
        return $this->cmdMessage;
    }

    public function getFrom()
    {
        if(!isset($this->cmdFrom)) {
            if($this->isCallback()) {
                $this->cmdFrom = $this->command->getCallbackQuery()->getFrom();
            } else {
                $this->cmdFrom = $this->getMessage()->getFrom();
            }
        }
        return $this->cmdFrom;
    }

    public function getConversation(string $conversationKey)
    {
        $chatId = null;
        $fromId = null;

        $message = $this->getMessage();
        if($message) $chatId = $message->getChat()->getId();

        $from = $this->getFrom();
        if($from) $fromId = $from->getId();

        return new Conversation($conversationKey, $fromId, $chatId);
    }

    public function getUser()
    {
        if(!isset($this->currUser)) {
            $chatId = $this->getMessage()->getChat()->getId();
            $this->currUser = TelegramUser::findByChatId($chatId);
        }
        return $this->currUser;
    }

    public function getRequestTarget()
    {
        if(!isset($this->reqTarget)) {

            $message = $this->getMessage();
            $chat = $message->getChat();

            $reqData = new RequestData();
            $reqData->chatId = $chat->getId();
            if($chat->isSuperGroup()) {
                $messageThreadId = $message->getMessageThreadId();
                if($messageThreadId) $reqData->messageThreadId = $messageThreadId;
            }
            $this->reqTarget = $reqData->build();

        }
        return $this->reqTarget;
    }

    public function request(string $classPath, array $args = [])
    {
        $classPathArr = explode('/', $classPath);
        $className = 'App\\TelegramRequest\\' . implode('\\', $classPathArr);
        $filePath = __DIR__."/../TelegramRequest/$classPath.php";

        require_once $filePath;
        $request = empty($args) ? new $className() : new $className(...$args);
        return $request;
    }

    public function toDebugRequest($data, array $config = [])
    {
        $request = $this->request('Debug/TextDebug');
        $request->params->chatId = \App\Config\AppConfig::$DEV_CHAT_ID;
        $request->setDebugData($data, $config);
        return $request;
    }

    public function sendEmptyResponse()
    {
        return Request::emptyResponse();
    }

    // public static function handle(string $handlerName, array $args = [])
    // {
    //     $controller = get_called_class();
    //     $controller = empty($args) ? new $controller() : new $controller(...$args);

    //     if(method_exists($controller, 'beforeHandle')) {
    //         $isContinue = true;
    //         $next = function(bool $continue) use (&$isContinue) {
    //             $isContinue = $continue;
    //         };

    //         $response = $controller->beforeHandle($next);
    //         if(!$isContinue) return $response;
    //     }

    //     $response = $controller->$handlerName();
    //     if(!method_exists($controller, 'afterHandle')) {
    //         return $response;
    //     }

    //     return $controller->afterHandle();
    // }
}