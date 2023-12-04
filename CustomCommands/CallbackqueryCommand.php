<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Conversation;

use App\Core\CallbackData;
use App\Controller\BotController;
use App\Controller\Bot\UserController;
use App\Controller\Bot\AdminController;
use App\Controller\Bot\AlarmController;
use App\Controller\Bot\PicController;
use App\Controller\Bot\PortController;
use App\Controller\Bot\RtuController;
use App\Controller\Bot\AlertController;
use App\Controller\Bot\TestController;

useHelper('telegram-callback');

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
        $decodedCallbackData = decodeCallbackData($callbackData);
        
        $fromUserId = null;
        if($callbackQuery->getFrom() && $callbackQuery->getFrom()->getId()) {
            $fromUserId = $callbackQuery->getFrom()->getId();
        }

        $decCallbackData = CallbackData::decode($callbackData);
        if($decCallbackData instanceof CallbackData) {
            
            $hasAccess = !$fromUserId || $decCallbackData->hasAccess($fromUserId);
            if(!$hasAccess) {
                return $callbackQuery->answer([
                    'text'       => 'Anda tidak memiliki akses!',
                    'show_alert' => true,
                    'cache_time' => 10,
                ]);
            }

            if($methodName = $decCallbackData->isCallbackOf(TestController::$callbacks)) {
                TestController::$command = $this;
                TestController::$methodName($decCallbackData->value, $callbackQuery);
                return $callbackQuery->answer();
            }

            if($methodName = $decCallbackData->isCallbackOf(PortController::$callbacks)) {
                PortController::$command = $this;
                PortController::$methodName($decCallbackData->value, $callbackQuery);
                return $callbackQuery->answer();
            }

            if($methodName = $decCallbackData->isCallbackOf(AdminController::$callbacks)) {
                AdminController::$command = $this;
                AdminController::$methodName($decCallbackData->value, $callbackQuery);
                return $callbackQuery->answer();
            }

            if($methodName = $decCallbackData->isCallbackOf(AlarmController::$callbacks)) {
                AlarmController::$command = $this;
                AlarmController::$methodName($decCallbackData->value, $callbackQuery);
                return $callbackQuery->answer();
            }

            if($methodName = $decCallbackData->isCallbackOf(UserController::$callbacks)) {
                UserController::$command = $this;
                UserController::$methodName($decCallbackData->value, $callbackQuery);
                return $callbackQuery->answer();
            }

        }

        if($decodedCallbackData) {

            if($methodName = $this->isCallbackOf(UserController::class, $decodedCallbackData)) {
                $this->callHandler(
                    UserController::class,
                    $methodName,
                    $callbackQuery,
                    $decodedCallbackData
                );
                return $callbackQuery->answer();
            }

            if($methodName = $this->isCallbackOf(RtuController::class, $decodedCallbackData)) {
                $this->callHandler(
                    RtuController::class,
                    $methodName,
                    $callbackQuery,
                    $decodedCallbackData
                );
                return $callbackQuery->answer();
            }
            
        }

        AdminController::$command = $this;
        if(BotController::catchCallback(AdminController::class, $callbackData, $callbackQuery)) {
            return $callbackQuery->answer();
        }

        PicController::$command = $this;
        if(BotController::catchCallback(PicController::class, $callbackData, $callbackQuery)) {
            return $callbackQuery->answer();
        }

        PortController::$command = $this;
        if(BotController::catchCallback(PortController::class, $callbackData, $callbackQuery)) {
            return $callbackQuery->answer();
        }

        AlertController::$command = $this;
        if(BotController::catchCallback(AlertController::class, $callbackData, $callbackQuery)) {
            return $callbackQuery->answer();
        }

        TestController::$command = $this;
        if(BotController::catchCallback(TestController::class, $callbackData, $callbackQuery)) {
            return $callbackQuery->answer();
        }

        return $callbackQuery->answer([
            'text'       => 'Content of the callback data: ' . $callbackData,
            'show_alert' => true,
            'cache_time' => 10,
        ]);
    }

    private function isCallbackOf($controller, $decodedCallbackData)
    {
        if(!is_array($decodedCallbackData)) {
            return null;
        }

        $callbacks = $controller::$callbacks ?? [];
        $currCallbackKey = isset($decodedCallbackData['callbackKey']) ? $decodedCallbackData['callbackKey'] : null;

        if(!array_key_exists($currCallbackKey, $callbacks)) {
            return null;
        }
        return $callbacks[$currCallbackKey];
    }

    private function callHandler($controller, $methodName, $callbackQuery, $decodedCallbackData)
    {
        $callbackData = [
            'title' => $decodedCallbackData['optionTitle'],
            'value' => $decodedCallbackData['optionValue'],
        ];
        $controller::$command = $this;
        $controller::$methodName($callbackData, $callbackQuery);
    }
}