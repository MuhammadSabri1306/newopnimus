<?php
namespace App\Config;

use App\Helper\Helper;

class AppConfig
{
    public static $MODE = null;
    public static $PUBLIC_URL = null;
    public static $DATABASE = null;

    public static $DEV_CHAT_ID = null;
    public static $LOG_CHAT_ID = null;

    public static $OSASEAPI_APP_ID = null;
    public static $OSASEAPI_TOKEN = null;
    public static $OSASEAPI_DBTABLE = null;

    public static $DENSUS_HOST_CLIENT_CREDENTIAL = null;
    public static $DENSUS_HOST_STATUS_URL = null;

    protected static $ERR_EXCLUSIONS = [];

    public static function initConfig()
    {
        static::$MODE = Helper::env('APP_MODE');

        $publicUrl = null;
        if(static::$MODE == 'development') $publicUrl = Helper::env('DEV_PUBLIC_URL');
        if(!$publicUrl) $publicUrl = Helper::env('PUBLIC_URL');
        static::$PUBLIC_URL = $publicUrl;

        $dbDefault = new \stdClass();
        $dbDefault->host = Helper::env('MYSQL_DEFAULT_HOST');
        $dbDefault->port = Helper::env('MYSQL_DEFAULT_PORT', null);
        $dbDefault->username = Helper::env('MYSQL_DEFAULT_USERNAME');
        $dbDefault->password = Helper::env('MYSQL_DEFAULT_PASSWORD');
        $dbDefault->name = Helper::env('MYSQL_DEFAULT_DATABASE');

        $db = new \stdClass();
        $db->default = $dbDefault;
        static::$DATABASE = $db;

        static::$DEV_CHAT_ID = Helper::env('DEV_TEST_CHAT_ID');
        static::$LOG_CHAT_ID = Helper::env('DEV_LOG_CHAT_ID');

        static::$OSASEAPI_APP_ID = Helper::env('OSASEAPI_APP_ID');
        static::$OSASEAPI_TOKEN = Helper::env('OSASEAPI_TOKEN');
        static::$OSASEAPI_DBTABLE = Helper::env('OSASEAPI_MYSQL_TABLE');

        static::$DENSUS_HOST_CLIENT_CREDENTIAL = '$2y$10$SghyVeUtHk3HU/xtg/bUFOZQBaRsvhiqjatZVVlb.SxrdqLmJNxiu';
        static::$DENSUS_HOST_STATUS_URL = 'https://densus.telkom.co.id/crons/node-crons/src/opnimus-alerting-port-v5/api/status.php';

        static::$ERR_EXCLUSIONS = [
            'error' => [],
            'warning' => [],
            'notice' => [],
        ];
    }

    public static function addErrorExclusions(string $severity, callable $checker)
    {
        $severities = array_keys(static::$ERR_EXCLUSIONS);
        if(!in_array($severity, $severities)) {
            $severitiesText = implode('|', $severities);
            throw new \Error("first params expected:$severitiesText, given:$severity");
        }
        array_push(static::$ERR_EXCLUSIONS[$severity], $checker);
    }

    public static function isErrorExcluded($err, string $severity = 'error')
    {
        $severities = array_keys(static::$ERR_EXCLUSIONS);
        if(!in_array($severity, $severities)) {
            $severitiesText = implode('|', $severities);
            throw new \Error("first params expected:$severitiesText, given:$severity");
        }

        $errExclusions = static::$ERR_EXCLUSIONS[$severity];
        for($i=0; $i<count($errExclusions); $i++) {

            $checker = $errExclusions[$i];
            if($checker($err)) {
                $i = count($errExclusions);
                return true;
            }

        }
        return false;
    }
}

AppConfig::initConfig();