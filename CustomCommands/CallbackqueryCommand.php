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
use App\Controller\Bot\StatisticController;
use App\Controller\Bot\ManagementUserController;
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
        BotController::$command = $this;

        $callbackQuery = BotController::$command->getCallbackQuery();
        $callbackData  = $callbackQuery->getData();
        
        $fromUserId = null;
        if(BotController::getFrom() && BotController::getFrom()->getId()) {
            $fromUserId = BotController::getFrom()->getId();
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

            $controllers = [
                TestController::class,
                PortController::class,
                AdminController::class,
                AlarmController::class,
                UserController::class,
                StatisticController::class,
                AlertController::class,
                RtuController::class,
                PicController::class,
                ManagementUserController::class,
            ];

            foreach($controllers as $controller) {
                if($methodName = $decCallbackData->isCallbackOf($controller::$callbacks)) {
                    $controller::$methodName($decCallbackData->value, $callbackQuery);
                    return $callbackQuery->answer();
                }
            }

        }

        $controllers = [
            AdminController::class,
            PicController::class,
            PortController::class,
            AlertController::class,
            ManagementUserController::class,
            TestController::class,
        ];

        foreach($controllers as $controller) {
            if(BotController::catchCallback($controller, $callbackData, $callbackQuery)) {
                return $callbackQuery->answer();
            }
        }

        return $callbackQuery->answer([
            'text'       => 'Content of the callback data: ' . $callbackData,
            'show_alert' => true,
            'cache_time' => 10,
        ]);
    }
}