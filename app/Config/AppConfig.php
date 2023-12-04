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
    }
}

AppConfig::initConfig();