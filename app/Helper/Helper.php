<?php
namespace App\Helper;

class Helper
{
    public static function env(string $key, $default = null) {
        if(isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        return $default;
    }

    public static function basePath(string $targetPath)
    {
        $pathArr = explode('/', $targetPath);
        return __DIR__.'/../../'. implode('/', $pathArr);
    }

    public static function appPath(string $targetPath)
    {
        $pathArr = explode('/', $targetPath);
        return __DIR__.'/../'. implode('/', $pathArr);
    }

    public static function debug(...$vars) {
        echo '<style>';
        echo 'pre { background-color: #f6f8fa; padding: 10px; }';
        echo 'strong { color: #e91e63; }';
        echo '</style>';
    
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
        die();
    }

    public static function debugJson(...$vars) {
        header('Content-Type: application/json; charset=utf-8');
        
        if(count($vars <= 1)) {
            if(count($vars) == 1) {
                echo json_encode(array_values($vars)[0], JSON_INVALID_UTF8_IGNORE);
            }
            die();
        }

        echo json_encode($vars, JSON_INVALID_UTF8_IGNORE);
        die();
    }
}