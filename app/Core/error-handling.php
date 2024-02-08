<?php

use MuhammadSabri1306\MyBotLogger\Entities\WarningLogger;
use App\Core\Exception\PHPWarningException;
use App\Core\Exception\PHPNoticeException;
use App\Config\AppConfig;

set_error_handler(function ($errNo, $errMsg, $errFile = null, $errLine = null, array $errContext = []) {

    if(AppConfig::$MODE == 'production') {

        // if($errNo === E_WARNING) {
        //     $err = new PHPWarningException("WARNING:$errMsg", 0);
        //     \MuhammadSabri1306\MyBotLogger\Entities\WarningLogger::catch($err);
        // }
    
        // if($errNo === E_NOTICE) {
        //     $err = new PHPNoticeException("NOTICE:$errMsg", 0);
        //     \MuhammadSabri1306\MyBotLogger\Entities\WarningLogger::catch($err);
        // }

    } elseif(AppConfig::$MODE == 'development') {

        if($errNo === E_WARNING) {
            throw new PHPWarningException("WARNING:$errMsg", 0);
        }
    
        if($errNo === E_NOTICE) {
            throw new PHPNoticeException("NOTICE:$errMsg", 0);
        }

    }

});