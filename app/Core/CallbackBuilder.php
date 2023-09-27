<?php
namespace App\Core;

use App\Core\DB;

class CallbackBuilder
{
    private $tableName = 'telegram_callback_query';
    
    public $controllerName;
    public $methodName;

    public $question;
    public $title;
    public $value;

    public function register()
    {
        $data = [
            'controller_name' => $this->$controllerName
        ];
    }

    public static function createOption($controllerName, $methodName)
    {
        $option = new CallbackBuilder();
        $option->controllerName = $controllerName;
        $option->question = null;
        $option->title = null;
        $option->value = null;
        return $option;
    }
}