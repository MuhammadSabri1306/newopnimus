<?php
namespace App\Core;

class SessionDebugger
{
    private static $sessionName = 'newopnimus_bot_session_debug123';

    private static function checkExpiredLog()
    {
        if(!isset($_SESSION[SessionDebugger::$sessionName])) return;
        $data = $_SESSION[SessionDebugger::$sessionName];
        if(count($data) < 1) return;

        $newData = [];
        $currTime = time();

        foreach($data as $item) {
            if($item->expiredAt < $currTime) {
                array_push($newData, $item);
            }
        }

        if(count($newData) > 0) {
            $_SESSION[SessionDebugger::$sessionName] = $newData;
        }
    }

    public static function record($key, $value)
    {
        SessionDebugger::checkExpiredLog();

        if(!isset($_SESSION[SessionDebugger::$sessionName])) {
            $_SESSION[SessionDebugger::$sessionName] = [];
        }

        $data = $_SESSION[SessionDebugger::$sessionName];
        $item = [
            'key' => $key,
            'value' => $value,
            'expiredAt' => time() + (30 * 60)
        ];

        array_push($data, (object) $item);
        $_SESSION[SessionDebugger::$sessionName] = $data;
    }

    public static function getAll()
    {
        SessionDebugger::checkExpiredLog();
        if(!isset($_SESSION[SessionDebugger::$sessionName])) {
            return [];
        }

        return $_SESSION[SessionDebugger::$sessionName];
    }

    public static function clear()
    {
        SessionDebugger::checkExpiredLog();
        if(!isset($_SESSION[SessionDebugger::$sessionName])) {
            return;
        }

        unset($_SESSION[SessionDebugger::$sessionName]);
    }
}