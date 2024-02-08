<?php

use App\Core\Exception\PHPWarningException;
use App\Core\Exception\PHPNoticeException;

set_error_handler(function ($errNo, $errMsg, $errFile = null, $errLine = null, array $errContext = []) {

    // if($errNo === E_WARNING) {
    //     $err = new PHPWarningException("WARNING:$errMsg", 0);
    //     \MuhammadSabri1306\MyBotLogger\Entities\WarningLogger::catch($err);
    // }

    // if($errNo === E_NOTICE) {
    //     $err = new PHPNoticeException("NOTICE:$errMsg", 0);
    //     \MuhammadSabri1306\MyBotLogger\Entities\WarningLogger::catch($err);
    // }

});