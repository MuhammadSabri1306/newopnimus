<?php
namespace App\Controller\Bot;

use App\Controller\BotController;

class PortController extends BotController
{
    public static $callbacks = [
        'port.reg' => 'onSelectRegional',
        'port.wit' => 'onSelectWitel',
        'port.loc' => 'onSelectLocation',
        'port.rtu' => 'onSelectRtu',
        'port.port' => 'onSelectPort',

        'portlog.reg' => 'onSelectRegionalLog',
        'portlog.wit' => 'onSelectWitelLog',
        'portlog.loc' => 'onSelectLocationLog',
        'portlog.rtu' => 'onSelectRtuLog',

        'portsts.type' => 'onSelectStatusType',
        'portstsa.reg' => 'onSelectRegionalStatusCatuan',
        'portstsa.wit' => 'onSelectWitelStatusCatuan',

        'portpue.reg' => 'onSelectRegionalPue',
        'portpue.wit' => 'onSelectWitelPue',
    ];

    public static function getCekPortAllConversation($isRequired = false, $chatId = null, $fromId = null)
    {
        $conversation = static::getConversation('cekportall', $chatId, $fromId);
        if($isRequired && !$conversation->isExists()) {
            return null;
        }
        return $conversation;
    }

    public static function checkPort()
    {
        return static::callModules('check-port');
    }

    protected static function showSelectPort($rtuSname)
    {
        return static::callModules('show-select-port', compact('rtuSname'));
    }

    protected static function showTextRtuPorts($rtuSname)
    {
        return static::callModules('show-text-rtu-ports', compact('rtuSname'));
    }

    protected static function showTextPort($rtuSname, $portParams = [])
    {
        return static::callModules('show-text-port', compact('rtuSname', 'portParams'));
    }

    protected static function getPortChart($portId)
    {
        return static::callModules('get-port-chart', compact('portId'));
    }

    public static function checkLog()
    {
        return static::callModules('check-log');
    }

    public static function checkPue()
    {
        return static::callModules('check-pue');
    }

    public static function showTextPortPue($regionalId, $witelId = null)
    {
        return static::callModules('show-text-port-pue', compact('regionalId', 'witelId'));
    }

    public static function onSelectRegional($regionalId)
    {
        return static::callModules('on-select-regional', compact('regionalId'));
    }

    public static function onSelectWitel($witelId)
    {
        return static::callModules('on-select-witel', compact('witelId'));
    }

    public static function onSelectLocation($locationId)
    {
        return static::callModules('on-select-location', compact('locationId'));
    }

    public static function onSelectRtu($rtuSname)
    {
        $message = static::getMessage();
        $chatId = $message->getChat()->getId();
        $messageId = $message->getMessageId();

        static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();
        return static::showSelectPort($rtuSname);
    }

    public static function onSelectPort($callbackValue)
    {
        return static::callModules('on-select-port', compact('callbackValue'));
    }

    public static function onSelectRtuLog($rtuSname, $callbackQuery)
    {   
        return static::callModules('on-select-rtu-log', compact('rtuSname', 'callbackQuery'));
    }

    public static function onSelectLocationLog($locId)
    {   
        return static::callModules('on-select-loc-log', compact('locId'));
    }

    public static function onSelectWitelLog($witelId)
    {   
        return static::callModules('on-select-witel-log', compact('witelId'));
    }

    public static function onSelectRegionalLog($regionalId)
    {   
        return static::callModules('on-select-regional-log', compact('regionalId'));
    }

    public static function checkStatus()
    {
        return static::callModules('check-status');
    }

    public static function onSelectStatusType($statusTypeKey, $callbackQuery)
    {
        return static::callModules(
            'on-select-status-type',
            compact('statusTypeKey', 'callbackQuery')
        );
    }

    public static function onSelectRegionalStatusCatuan($regionalId, $callbackQuery)
    {
        return static::callModules(
            'on-select-regional-status-catuan',
            compact('regionalId', 'callbackQuery')
        );
    }

    public static function onSelectWitelStatusCatuan($witelId, $callbackQuery)
    {
        return static::callModules(
            'on-select-witel-status-catuan',
            compact('witelId', 'callbackQuery')
        );
    }

    public static function onSelectWitelPue($witelId)
    {   
        return static::callModules('on-select-witel-pue', compact('witelId'));
    }

    public static function onSelectRegionalPue($regionalId)
    {   
        return static::callModules('on-select-regional-pue', compact('regionalId'));
    }
}