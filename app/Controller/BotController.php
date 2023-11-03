<?php
namespace App\Controller;

use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\ChatAction;
use App\Core\RequestData;
use App\Core\Controller;
use App\Core\Exception\TelegramResponseException;

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
        global $appConfig;

        $chatId = isset($config['chatId']) ? $config['chatId'] : $appConfig->userTesting->chatId;
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

    public static function request(string $classPath, array $args = [])
    {
        $classPathArr = explode('/', $classPath);
        $className = 'App\\TelegramRequest\\' . implode('\\', $classPathArr);
        $filePath = __DIR__."/../TelegramRequest/$classPath.php";

        require_once $filePath;
        return empty($args) ? new $className() : new $className(...$args);
    }

    public static function sendErrorMessage()
    {
        if(static::$command) {
            $text = '*Tidak dapat merespon Permintaan Anda.*'.PHP_EOL.
                'Terjadi masalah saat memproses permintaan anda, silahkan menunggu beberapa saat.'.
                ' Anda juga dapat melaporkan kepada Tim Pengembang sebagai bug jika Error tetap berlanjut.';
            static::$command->replyToChat($text, [ 'parse_mode' => 'markdown' ]);
        }
    }

    public static function catchErrorRequest(ServerResponse $response)
    {
        if($response->isOk()) return $response;
        throw new TelegramResponseException($response);
    }
}