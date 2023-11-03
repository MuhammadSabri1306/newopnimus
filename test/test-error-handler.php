<?php
namespace Longman\TelegramBot\Commands\SystemCommands;
require __DIR__.'/../app/bootstrap.php';

\MuhammadSabri1306\MyBotLogger\Logger::$botToken = $config['api_key'];
\MuhammadSabri1306\MyBotLogger\Logger::$botUsername = $config['bot_username'];
\MuhammadSabri1306\MyBotLogger\Logger::$chatId = '-4092116808';

class AnotherClass {
    public function run()
    {
        $PDO = new \PDO('...') ;
    }
}

class TestCommand
{
    public function testError()
    {
        try {
            $className = AnotherClass::class;
            $class = new $className();
            $class->run();
        } catch(\Throwable $err) {
            $test = \MuhammadSabri1306\MyBotLogger\Entities\ErrorLogger::catch($err);
            dd($test);
        }
    }
}

$testComm = new TestCommand();
$testComm->testError();
echo 'Success';