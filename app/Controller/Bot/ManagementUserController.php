<?php
namespace App\Controller\Bot;

use App\Controller\BotController;
use App\Model\TelegramAdmin;

class ManagementUserController extends BotController
{
    public static $callbacks = [
        'mngusr.unv' => 'onSelectUnavailableFeature',
        'mngusr.rmuser' => 'onSelectRemoveUser',
        'mngusr.rmusertreg' => 'onSelectRegionalRemoveUser',
        'mngusr.rmuserwit' => 'onSelectWitelRemoveUser',
        'mngusr.rmuserappr' => 'onSelectRemoveUserApproval',
        'mngusr.rmpic' => 'onSelectRemovePic',
        'mngusr.rmpictreg' => 'onSelectRegionalRemovePic',
        'mngusr.rmpictwit' => 'onSelectWitelRemovePic',
        'mngusr.rmpicappr' => 'onSelectRemovePicApproval',
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

    public static function getRmPicConversation($isRequired = false, $chatId = null, $fromId = null)
    {
        $conversation = static::getConversation('admin_rm_pic', $chatId, $fromId);

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

        $rmPicConversation = static::getRmPicConversation();
        if($rmPicConversation->isExists()) {
            $response = static::removePic();
            if($response) return $response;
        }

        return static::menu();
    }

    public static function menu()
    {
        $request = static::request('ManagementUser/SelectMenu');
        $request->setTarget( static::getRequestTarget() );
        $isDeveloper = static::getMessage()->getChat()->getId() == \App\Config\AppConfig::$DEV_CHAT_ID;
        $request->setInKeyboard(function($inKeyboard) use ($isDeveloper) {
            $inKeyboard['removeUser']['callback_data'] = 'mngusr.rmuser';
            $inKeyboard['removePic']['callback_data'] = !$isDeveloper ? 'mngusr.unv' : 'mngusr.rmpic';
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

    public static function removePic()
    {
        return static::callModules('remove-pic');
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

    public static function onSelectRemovePic()
    {
        return static::callModules('on-select-remove-pic');
    }

    public static function onSelectRegionalRemoveUser($regionalId)
    {
        return static::callModules('on-select-regional-remove-user', compact('regionalId'));
    }

    public static function onSelectRegionalRemovePic($regionalId)
    {
        return static::callModules('on-select-regional-remove-pic', compact('regionalId'));
    }

    public static function onSelectWitelRemoveUser($witelId)
    {
        return static::callModules('on-select-witel-remove-user', compact('witelId'));
    }

    public static function onSelectWitelRemovePic($witelId)
    {
        return static::callModules('on-select-witel-remove-pic', compact('witelId'));
    }

    public static function onSelectRemoveUserApproval($telgUserId)
    {
        return static::callModules('on-select-remove-user-approval', compact('telgUserId'));
    }

    public static function onSelectRemovePicApproval($telgUserId)
    {
        return static::callModules('on-select-remove-pic-approval', compact('telgUserId'));
    }
}