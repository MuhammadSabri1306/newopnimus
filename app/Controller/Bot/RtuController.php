<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\ChatAction;

use App\Core\RequestData;
use App\BuiltMessageText\UserText;
use App\BuiltMessageText\PortText;
use App\Request\RequestInKeyboard;

use App\Core\CallbackData;
use App\ApiRequest\NewosaseApi;
use App\ApiRequest\NewosaseApiV2;
use App\Controller\BotController;
use App\Model\TelegramUser;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\RtuLocation;
use App\Model\RtuList;

class RtuController extends BotController
{
    public static $callbacks = [
        'rtu.cekreg' => 'onSelectRegional',
        'rtu.cekwit' => 'onSelectWitel',
        'rtu.cekloc' => 'onSelectLocation',
        'rtu.cekrtu' => 'onSelectRtu',

        'rtu.listreg' => 'onListSelectRegional',
        'rtu.listwit' => 'onListSelectWitel',
    ];

    public static function checkRtu()
    {
        return static::callModules('check-rtu');
    }

    public static function onSelectRegional($regionalId)
    {
        return static::callModules('on-select-regional', compact('regionalId'));
    }

    public static function onSelectWitel($witelId)
    {
        return static::callModules('on-select-witel', compact('witelId'));
    }

    public static function onSelectLocation($locId)
    {
        return static::callModules('on-select-location', compact('locId'));
    }

    public static function onSelectRtu($rtuId)
    {
        $message = static::getMessage();
        $chatId = $message->getChat()->getId();
        $messageId = $message->getMessageId();

        static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();
        return static::showRtuDetail([ 'id' => $rtuId ]);
    }

    public static function showRtuDetail($rtu = [])
    {
        return static::callModules('show-rtu-detail', compact('rtu'));
    }

    public static function showWitelRtus($witelId)
    {
        return static::callModules('show-witel-rtus', compact('witelId'));
    }

    public static function listRtu()
    {
        return static::callModules('list-rtu');
    }

    public static function onListSelectRegional($regionalId)
    {
        return static::callModules('on-list-select-regional', compact('regionalId'));
    }

    public static function onListSelectWitel($witelId)
    {
        $message = static::getMessage();
        $chatId = $message->getChat()->getId();
        $messageId = $message->getMessageId();

        static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();
        return static::showWitelRtus($witelId);
    }
}