<?php
namespace App\Controller;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\ChatAction;
use App\Core\RequestData;
use App\Core\Controller;

class BotController extends Controller
{
    public static $command;

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
        $chatId = isset($config['chatId']) ? $config['chatId'] : 1931357638;
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
}