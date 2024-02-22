<?php

use MuhammadSabri1306\MyBotLogger\Entities\WarningLogger;
use App\Core\Exception\PHPWarningException;
use App\Core\Exception\PHPNoticeException;
use App\Controller\BotController;
use App\Config\AppConfig;

set_error_handler(function ($errNo, $errMsg, $errFile = null, $errLine = null, array $errContext = []) {

    if(AppConfig::$MODE == 'production') {

        if($errNo === E_WARNING) {
            $err = new PHPWarningException("WARNING:$errMsg", 0);
            if(!AppConfig::isErrorExcluded($err, 'warning')) {
                $logger = new \MuhammadSabri1306\MyBotLogger\Entities\WarningLogger($err);
                BotController::logError($logger);
            }
        }
    
        if($errNo === E_NOTICE) {
            $err = new PHPNoticeException("NOTICE:$errMsg", 0);
            if(!AppConfig::isErrorExcluded($err, 'notice')) {
                $logger = new \MuhammadSabri1306\MyBotLogger\Entities\WarningLogger($err);
                BotController::logError($logger);
            }
        }

    } elseif(AppConfig::$MODE == 'development') {

        if($errNo === E_WARNING) {
            throw new PHPWarningException("WARNING:$errMsg", 0);
        }
    
        if($errNo === E_NOTICE) {
            throw new PHPNoticeException("NOTICE:$errMsg", 0);
        }

    }

});