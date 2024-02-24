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

    public static function basePath(string $subPath = '')
    {
        $basePath = realpath(__DIR__.'/../..');
        $subPathArr = array_filter( explode('/', $subPath) );
        $subPath = implode('/', $subPathArr);
        return implode('/', [ $basePath, $subPath ]);
    }

    public static function appPath(string $subPath = '')
    {
        $subPath = implode('/', [ 'app', $subPath ]);
        return Helper::basePath($subPath);
    }

    public static function publicPath(string $subPath = '')
    {
        $subPath = implode('/', [ 'public', $subPath ]);
        return Helper::basePath($subPath);
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