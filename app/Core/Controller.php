<?php
namespace App\Core;

class Controller
{
    protected static function callModules($moduleName, $params = [])
    {
        $reflector = new \ReflectionClass(static::class);
        $classPath = $reflector->getFileName();
        
        $modulePath = str_replace('.php', '/', $classPath) . "$moduleName.php";
        if(count($params) > 0) extract($params);
        $result = require $modulePath;
        
        return $result;
    }
}