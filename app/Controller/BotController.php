<?php
namespace App\Controller;

use Longman\TelegramBot\Commands\Command;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Message;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\ChatAction;
use MuhammadSabri1306\MyBotLogger\Logger;
use App\Core\RequestData;
use App\Core\Conversation;
use App\Core\Controller;
use App\Core\Exception\TelegramResponseException;
use App\Core\CallbackAnswer;
use App\Model\TelegramUser;

class BotController extends Controller
{
    public static $command;
    private static $currTelegramUser = null;
    protected static $requestTarget = null;

    private static $isCmdCallback;
    private static $cmdMessage;
    private static $cmdFrom;
    private static $telgUser;
    private static $reqTarget;

    private static $conversations = [];

    public static function isCallbackCommand(): bool
    {
        if(!isset(static::$isCmdCallback)) {
            if(!isset(static::$command)) return false;
            static::$isCmdCallback = is_null(static::$command->getCallbackQuery()) ? false : true;
        }
        return static::$isCmdCallback;
    }

    public static function getMessage(): Message
    {
        if(!isset(static::$cmdMessage)) {
            if(!isset(static::$command)) return null;
            if(static::isCallbackCommand()) {
                static::$cmdMessage = static::$command->getCallbackQuery()->getMessage();
            } else {
                static::$cmdMessage = static::$command->getMessage();
            }
        }

        return static::$cmdMessage;
    }

    public static function getFrom()
    {
        if(!isset(static::$cmdFrom)) {
            if(static::isCallbackCommand()) {
                if(!isset(static::$command)) return null;
                static::$cmdFrom = static::$command->getCallbackQuery()->getFrom();
            } else {
                static::$cmdFrom = static::getMessage()->getFrom();
            }
        }
        return static::$cmdFrom;
    }

    public static function getUser()
    {
        if(!isset(static::$telgUser)) {
            $message = static::getMessage();
            if($message) {
                $chatId = $message->getChat()->getId();
                static::$telgUser = TelegramUser::findByChatId($chatId);
            }
        }
        return static::$telgUser;
    }

    public static function getRequestTarget()
    {
        if(!isset(static::$reqTarget)) {

            $message = static::getMessage();
            $chat = $message->getChat();

            $reqData = new RequestData();
            $reqData->chatId = $chat->getId();
            if($chat->isSuperGroup()) {
                $messageThreadId = $message->getMessageThreadId();
                if($messageThreadId) $reqData->messageThreadId = $messageThreadId;
            }
            static::$reqTarget = $reqData->build();

        }

        return static::$reqTarget;
    }

    public static function getConversation($conversationKey, $chatId = null, $fromId = null)
    {
        if(!$chatId) $chatId = static::getMessage()->getChat()->getId();
        if(!$fromId) $fromId = static::getFrom()->getId();

        $isExists = isset(static::$conversations[$conversationKey]);
        if($isExists) {
            $isExists = static::$conversations[$conversationKey]->getChatId() == $chatId;
            if($isExists) {
                $isExists = static::$conversations[$conversationKey]->getUserId() == $fromId;
            }
        }

        if(!$isExists) {
            static::$conversations[$conversationKey] = new Conversation($conversationKey, $fromId, $chatId);
        }

        return static::$conversations[$conversationKey];
    }

    public static function catchCallback($controller, $callbackData, $callbackQuery)
    {
        if(empty($callbackData)) return null;

        $callbacks = $controller::$callbacks ?? [];
        $targetKeyArr = explode('.', $callbackData);
        if(count($targetKeyArr) < 2 || empty($callbacks)) return null;
        
        $targetKey = $targetKeyArr[0].'.'.$targetKeyArr[1];
        $data = count($targetKeyArr) === 3 ? $targetKeyArr[2] : null;

        if(!array_key_exists($targetKey, $callbacks)) return null;
        $targetCallback = $callbacks[$targetKey];

        if(!method_exists($controller, $targetCallback)) {
            return null;
        }
        return call_user_func([$controller, $targetCallback], $data, $callbackQuery);
    }

    public static function catchError($err, $chatId)
    {
        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;
        $reqData->text = $err->getMessage();
        return Request::sendMessage($reqData->build());
    }

    public static function sendDebugMessage($data, array $config = [])
    {
        $chatId = isset($config['chatId']) ? $config['chatId'] : \App\Config\AppConfig::$DEV_CHAT_ID;
        $isCode = isset($config['isCode']) ? $config['isCode'] : true;
        $toJson = isset($config['toJson']) ? $config['toJson'] : true;

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $chatId;

        if($toJson) {
            $data = json_encode($data, JSON_INVALID_UTF8_IGNORE);
        }
        
        if($isCode) {
            $reqData->text = '```'.PHP_EOL.$data.'```';
        } else {
            $reqData->text = $data;
        }
        return Request::sendMessage($reqData->build());
    }

    public static function sendMessageList(RequestData $reqData, array $textList, $useTypingAction = false)
    {
        foreach($textList as $replyText) {
            if($useTypingAction) {
                $reqDataTyping = $reqData->duplicate('chatId');
                $reqDataTyping->action = ChatAction::TYPING;
                Request::sendChatAction($reqDataTyping->build());
            }


            $reqData->text = $replyText;
            $response = Request::sendMessage($reqData->build());

            if(!$response->isOk()) {
                return BotController::sendDebugMessage([
                    'response' => $response,
                    'text' => $reqData->text
                ]);
            }
        }

        return $response;
    }

    public static function getRequest(string $classPath, array $args = [])
    {
        $classPathArr = explode('/', $classPath);
        $className = 'App\\TelegramResponse\\' . implode('\\', $classPathArr);
        $filePath = __DIR__."/../TelegramResponse/$classPath.php";

        require_once $filePath;
        return new $className(...$args);
    }

    protected static function setRequestTarget($target)
    {
        $data = [];
        if($target instanceof Message) {

            $chat = $target->getChat();
            $reqData = new RequestData();
            $reqData->chatId = $chat->getId();
            if($chat->isSuperGroup()) {
                $messageThreadId = $target->getMessageThreadId();
                if($messageThreadId) $reqData->messageThreadId = $messageThreadId;
            }
            static::$requestTarget = $reqData;

        } elseif($target instanceof RequestData) {

            static::$requestTarget = $target->duplicate('chatId', 'messageThreadId');

        } elseif(is_array($target)) {

            $reqData = new RequestData();
            if(isset($target['chatId'])) $reqData->chatId = $target['chatId'];
            if(isset($target['messageThreadId'])) $reqData->messageThreadId = $target['messageThreadId'];
            static::$requestTarget = $reqData;

        } elseif(is_object($target)) {

            $reqData = new RequestData();
            if(isset($target->chatId)) $reqData->chatId = $target->chatId;
            if(isset($target->messageThreadId)) $reqData->messageThreadId = $target->messageThreadId;
            static::$requestTarget = $reqData;

        }
    }

    public static function request(string $classPath, array $args = [], $applyTarget = true)
    {
        $classPathArr = explode('/', $classPath);
        $className = 'App\\TelegramRequest\\' . implode('\\', $classPathArr);
        $filePath = __DIR__."/../TelegramRequest/$classPath.php";

        require_once $filePath;
        $request = empty($args) ? new $className() : new $className(...$args);

        if($applyTarget && static::$requestTarget) {
            $request->params->paste(
                static::$requestTarget->copy('chatId', 'messageThreadId')
            );
        }

        return $request;
    }

    public static function createCallbackAnswer(string $text = null, bool $showAlert = null, int $cacheTime = null)
    {
        return new CallbackAnswer($text, $showAlert, $cacheTime);
    }

    public static function sendErrorMessage()
    {
        if(static::$command) {
            $text = '*Tidak dapat merespon Permintaan Anda.*'.PHP_EOL.
                'Terjadi masalah saat memproses permintaan anda, silahkan menunggu beberapa saat.'.
                ' Anda juga dapat melaporkan kepada Tim Pengembang sebagai bug jika Error tetap berlanjut.';
            return static::$command->replyToChat($text, [ 'parse_mode' => 'markdown' ]);
        }
    }

    public static function catchErrorRequest(ServerResponse $response)
    {
        if($response->isOk()) return $response;
        throw new TelegramResponseException($response);
    }

    public static function sendEmptyResponse()
    {
        return Request::emptyResponse();
    }

    public static function setLoggerParams(Logger $logger)
    {
        $params = [];

        if(static::isCallbackCommand()) {
            $params['callback_data'] = static::$command->getCallbackQuery()->getData();
            $params['chat_id'] = static::getMessage()->getChat()->getId();
        } elseif(static::getMessage()) {
            $params['message_text'] = static::getMessage()->getText();
            $params['chat_id'] = static::getMessage()->getChat()->getId();
        }

        if(static::getFrom()) {
            $params['user_id'] = static::getFrom()->getId();
        }

        $logger->setParams($params);
        return $logger;
    }

    public static function logError(Logger $logger)
    {
        try {
            $logger = static::setLoggerParams($logger);
            $logger->log();
        } catch(\Throwable $err) {
            \MuhammadSabri1306\MyBotLogger\Entities\ErrorLogger::catch($err);
        }
    }
}