<?php
namespace App\Core\Exception;

use Longman\TelegramBot\Entities\ServerResponse;

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