<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use App\Core\Conversation;
use App\Core\CallbackData;
use App\Controller\BotController;
use App\Controller\Bot\AdminController;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\PicLocation;
use App\Model\Registration;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\AlertUsers;

class UserController extends BotController
{
    public static $callbacks = [
        'user.aggrmnt' => 'onRegist',
        'user.lvl' => 'onSelectLevel',
        'user.treg' => 'onSelectRegional',
        'user.witl' => 'onSelectWitel',
        'user.orgn' => 'onSelectOrganikStatus',
        'user.reset' => 'onRegistReset',
        'user.regcancel' => 'onRegistCancel',
    ];

    public static function getRegistConversation($isRequired = false, $chatId = null, $fromId = null)
    {
        $conversation = static::getConversation('regist', $chatId, $fromId);

        if($isRequired && !$conversation->isExists()) {
            $request = static::request('TextDefault');
            $request->setTarget( static::getRequestTarget() );
            $request->setText(function($text) {
                return $text->addText('Sesi anda telah berakhir. Mohon untuk melakukan permintaan')
                    ->addText(' ulang dengan mengetikkan perintah /start.');
            });
            $request->send();
            return null;
        }

        return $conversation;
    }

    public static function checkRegistStatus()
    {
        return static::callModules('check-regist-status');
    }

    public static function tou()
    {
        return static::callModules('tou');
    }

    public static function setRegistLevel()
    {
        return static::callModules('set-regist-level');
    }

    public static function resetRegistration()
    {
        return static::callModules('reset-registration');
    }

    public static function register()
    {
        return static::callModules('register');
    }

    public static function onRegist($isApproved)
    {
        return static::callModules('on-regist', compact('isApproved'));
    }

    public static function onSelectLevel($level)
    {
        return static::callModules('on-select-level', compact('level'));
    }

    public static function onSelectRegional($regionalId)
    {
        return static::callModules('on-select-regional', compact('regionalId'));
    }

    public static function onSelectWitel($witelId)
    {
        return static::callModules('on-select-witel', compact('witelId'));
    }

    public static function onSelectOrganikStatus($isOrganik)
    {
        return static::callModules('on-select-organik-status', compact('isOrganik'));
    }

    public static function onRegistReset($isApproved)
    {
        return static::callModules('on-regist-reset', compact('isApproved'));
    }

    protected static function submitRegistration()
    {
        return static::callModules('submit-registration');
    }

    public static function whenRegistApproved($registId)
    {   
        return static::callModules('when-regist-approved', compact('registId'));
    }

    public static function whenRegistRejected($registId)
    {
        return static::callModules('when-regist-rejected', compact('registId'));
    }

    public static function whenRegistCancel()
    {   
        return static::callModules('when-regist-cancel');
    }

    public static function onRegistCancel($registId)
    {   
        return static::callModules('on-regist-cancel', compact('registId'));
    }
}