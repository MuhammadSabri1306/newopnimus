<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\InlineKeyboard;

use App\Core\RequestData;
use App\Core\TelegramText;
use App\Controller\BotController;
use App\Controller\Bot\AdminController;

class TestController extends BotController
{
    protected static $callbacks = [
        'test.inkeyboard_json' => 'onSelectInKeyboardJson',
    ];

    public static function run()
    {
        $message = TestController::$command->getMessage();
        $messageText = strtolower(trim($message->getText(true)));
        $params = explode(' ', $messageText);
        $modulKey = array_shift($params);

        switch($modulKey) {
            case 'inkeyboardjson': return TestController::inKeyboardJson(...$params); break;
            case 'adminregistapproval': return TestController::adminRegistApproval(...$params); break;
            case 'errorlog': return TestController::errorLog(...$params); break;
            case 'errorresponse': return TestController::throwErrorResponse(...$params); break;
            default: return TestController::$command->replyToChat('This is TEST Command.');
        }
    }

    public static function inKeyboardJson()
    {
        $message = TestController::$command->getMessage();
        
        $reqData = new RequestData();
        $reqData->chatId = $message->getChat()->getId();
        $reqData->text = 'Test Inline Keyboard data berupa JSON.';

        function encodeKeyboardData($name, $data) {
            $dataJson = json_encode($data);
            return "$name.$dataJson";
        }

        $callbackData1 = [ 'id' => 1, 'name' => 'callback data 1' ];
        $callbackData2 = [ 'id' => 2, 'name' => 'callback data 2' ];
        $callbackData3 = [ 'id' => 3, 'name' => 'callback data 3' ];

        $reqData->replyMarkup = new InlineKeyboard([
            ['text' => 'Callback 1', 'callback_data' => encodeKeyboardData('test.inkeyboard_json', $callbackData1)],
            ['text' => 'Callback 2', 'callback_data' => encodeKeyboardData('test.inkeyboard_json', $callbackData2)],
            ['text' => 'Callback 3', 'callback_data' => encodeKeyboardData('test.inkeyboard_json', $callbackData3)],
        ]);

        return Request::sendMessage($reqData->build());
    }

    public static function adminRegistApproval($registId = null)
    {
        $message = TestController::$command->getMessage();
        
        $reqData = new RequestData();
        $reqData->chatId = $message->getChat()->getId();
        $reqData->parseMode = 'markdown';
        
        if(is_null($registId)) {
            $reqData->text = TelegramText::create('Format:')
                ->addCode('/test adminregistapproval [registration_id]')
                ->get();
            return Request::sendMessage($reqData->build());
        }

        $reqData->text = 'Test Regist Approval Admin, registId:'.$registId;
        $response = Request::sendMessage($reqData->build());

        AdminController::whenRegistUser($registId);
        return $response;
    }

    public static function onSelectInKeyboardJson($callbackData, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $callbackData = json_decode($callbackData);
        
        $reqData = new RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->text = TelegramText::create()
            ->addBold('Data Callback')->newLine(1)
            ->startCode()
            ->addText("ID   : $callbackData->id")->newLine()
            ->addText("NAME : $callbackData->name")
            ->endCode()
            ->get();

        return Request::sendMessage($reqData->build());
    }

    public static function errorLog()
    {
        $test = testError();
        // try {


        // } catch(\Throwable $err) {
        //     $test = \MuhammadSabri1306\MyBotLogger\Entities\ErrorLogger::catch($err);
        //     BotController::sendDebugMessage($test, [ 'toJson' => false ]);
        // } finally {
        //     // return Request::emptyResponse();
        //     return TestController::$command->replyToChat('TEST Logger');
        // }
        // BotController::sendDebugMessage(\MuhammadSabri1306\MyBotLogger\Logger::$botUsername);
        // return TestController::$command->replyToChat('TEST Logger');
    }

    public static function throwErrorResponse()
    {
        $reqData = new RequestData();
        $reqData->chatId = '-40921168081231';
        $reqData->text = 'Test Throw Error Response';
        return Request::sendMessage($reqData->build());
    }
}