<?php
namespace App\Controller\Bot;

use App\Controller\BotController;

class AlertController extends BotController
{
    public static $callbacks = [
        'alert.excl' => 'onStartRequestExclusion',
        'alert.mode' => 'onChangeMode',
    ];

    public static function getAlertExclusionConversation()
    {
        return static::getConversation('alert_exclusion');
    }

    public static function switch()
    {
        return static::callModules('switch');
    }

    public static function requestExclusion()
    {
        return static::callModules('request-exclusion');
    }

    public static function submitExclusionRequest()
    {
        return static::callModules('submit-exclusion-request');
    }

    public static function onStartRequestExclusion($isApproved)
    {
        return static::callModules('on-start-request-exclusion', compact('isApproved'));
    }

    public static function whenRequestExclusionReviewed(bool $isApproved, $registId)
    {
        return static::callModules('when-request-exclusion-reviewed', compact('isApproved', 'registId'));
    }

    public static function changeAlertMode()
    {
        return static::callModules('change-alert-mode');
    }

    public static function onChangeMode($modeId)
    {
        return static::callModules('on-change-mode', compact('modeId'));
    }
}