<?php
namespace App\Controller\Bot;

use App\Controller\BotController;
use App\Model\TelegramAdmin;
use App\Model\PicLocation;

class ManagementUserController extends BotController
{
    public static $callbacks = [
        'mngusr.unv' => 'onSelectUnavailableFeature',
        'mngusr.rmuser' => 'onSelectRemoveUser',
        'mngusr.rmusertreg' => 'onSelectRegionalRemoveUser',
        'mngusr.rmuserwit' => 'onSelectWitelRemoveUser',
        'mngusr.rmuserappr' => 'onSelectRemoveUserApproval',
    ];

    public static function getRmUserConversation($isRequired = false, $chatId = null, $fromId = null)
    {
        $conversation = static::getConversation('admin_rm_user', $chatId, $fromId);

        if($isRequired && !$conversation->isExists()) {
            $request = static::request('TextDefault');
            $request->setTarget( static::getRequestTarget() );
            $request->setText(function($text) {
                return $text->addText('Sesi anda telah berakhir. Mohon untuk melakukan permintaan')
                    ->addText(' ulang dengan mengetikkan perintah /user_management.');
            });
            $request->send();
            return null;
        }

        return $conversation;
    }

    protected static function getAdmin()
    {
        $chatId = static::getMessage()->getChat()->getId();
        return TelegramAdmin::findByChatId($chatId);
    }

    public static function manage()
    {
        $admin = static::getAdmin();
        if(!$admin) return static::sendEmptyResponse();

        $rmUserConversation = static::getRmUserConversation();
        if($rmUserConversation->isExists()) {
            $response = static::removeUser();
            if($response) return $response;
        }

        return static::menu();
    }

    public static function menu()
    {
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

    public static function removeUser()
    {
        return static::callModules('remove-user');
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

    public static function onSelectRegionalRemoveUser($regionalId)
    {
        return static::callModules('on-select-regional-remove-user', compact('regionalId'));
    }

    public static function onSelectWitelRemoveUser($witelId)
    {
        return static::callModules('on-select-witel-remove-user', compact('witelId'));
    }

    public static function onSelectRemoveUserApproval($telgUserId)
    {
        return static::callModules('on-select-remove-user-approval', compact('telgUserId'));
    }
}