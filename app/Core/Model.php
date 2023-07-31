<?php
namespace App\Core;

class Model
{
    protected static $table;

    public static function query(callable $callback)
    {
        if (is_callable($callback)) {

            $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
            $modelClass = $backtrace[1]['class'];

            $db = new DB();
            $table = $modelClass::$table ?? Model::$table;
            if($table) {
                return $callback($db, $modelClass::$table);
            }

            $classParts = explode('\\', $modelClass);
            $modelName = end($classParts);
            throw new \BadFunctionCallException('$table'." property must defined in $modelName or Model.");

        } else {
            throw new \InvalidArgumentException('Parameter must be a callable function.');
        }
    }

    protected function loadModule($moduleName)
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $modelClass = $backtrace[1]['class'];

        $classParts = explode('\\', $modelClass);
        $modelName = end($classParts);

        $modulePath = __DIR__ . "/../model/$modelName/$moduleName.php";

        if (file_exists($modulePath)) {
            $data = new \stdClass();
            require $modulePath;
            return $data;
        } else {
            throw new Exception("Model's module '$moduleName' not found.");
        }
    }
}