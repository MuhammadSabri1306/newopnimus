<?php
require __DIR__.'/../app/bootstrap.php';

use Longman\TelegramBot\Entities\ServerResponse;

\MuhammadSabri1306\MyBotLogger\Logger::$botToken = $config['api_key'];
\MuhammadSabri1306\MyBotLogger\Logger::$botUsername = $config['bot_username'];
\MuhammadSabri1306\MyBotLogger\Logger::$chatId = '-4092116808';

class TelegramResponseException extends \Exception
{
    private $response;

    public function __construct(ServerResponse $response)
    {
        parent::__construct($response->printError(true));
        $this->response = $response->raw_data;
    }

    public function getResponseData()
    {
        return $this->response;
    }
}

/*
{
    "ok": false,
    "error_code": 400,
    "description": "Bad Request: chat not found"
}
*/

$responseData = [ 'ok' => false, 'error_code' => 400, 'description' => 'Bad Request: chat not found' ];
$response = new ServerResponse($responseData);
try {
    throw new TelegramResponseException($response);
} catch(TelegramResponseException $err) {
    \MuhammadSabri1306\MyBotLogger\Entities\TelegramResponseLogger::catch($err);
}