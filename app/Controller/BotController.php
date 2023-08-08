<?php
namespace App\Controller;

use Longman\TelegramBot\Request;
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
}