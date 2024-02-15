<?php
namespace App\Controller\Bot;

use App\Controller\BotController;
use App\Model\TelegramAdmin;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\PicLocation;

class ManagementUserController extends BotController
{
    public static $callbacks = [
        'mngusr.unv' => 'onSelectUnavailableFeature',
        'mngusr.rmuser' => 'onSelectRemoveUser',
        'mngusr.rmusertreg' => 'onSelectRegionalRemoveUser',
    ];

    protected static function getAdmin()
    {
        $chatId = static::getMessage()->getChat()->getId();
        return TelegramAdmin::findByChatId($chatId);
    }

    public static function menu()
    {
        $admin = static::getAdmin();
        if(!$admin) return static::sendEmptyResponse();

        $request = static::request('ManagementUser/SelectMenu');
        $request->setTarget( static::getRequestTarget() );
        $request->setInKeyboard(function($inKeyboard) {
            $inKeyboard['removeUser']['callback_data'] = 'mngusr.rmuser';
            $inKeyboard['removePic']['callback_data'] = 'mngusr.unv';
            $inKeyboard['removeAdmin']['callback_data'] = 'mngusr.unv';
            $inKeyboard['assignPic']['callback_data'] = 'mngusr.unv';
            return $inKeyboard;

        });
        return $request->send();
    }

    public static function onSelectUnavailableFeature()
    {
        $request = static::request('TextDefault');
        $request->setTarget( static::getRequestTarget() );
        $request->setText(fn($text) => $text->addText('Fitur belum tersedia.'));
        return $request->send();
    }

    public static function onSelectRemoveUser()
    {
        return static::callModules('on-select-remove-user');
    }
}