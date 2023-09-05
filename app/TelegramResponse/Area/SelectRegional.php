<?php
namespace App\TelegramResponse\Area;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramResponse;
use App\Core\RequestData;
use App\Core\TelegramText;

use App\Model\Regional;

class SelectRegional extends TelegramResponse
{
    private $chatId;
    private $requestData;
    private $regionals;

    public function __construct($chatId)
    {
        $this->chatId = $chatId;
        $this->regionals = Regional::getSnameOrdered();
        $this->setRequest();
    }

    public function getText()
    {
        return TelegramText::create('Silahkan pilih ')
            ->addBold('Regional')
            ->addText('.');
    }

    public function setRequest(callable $callButton = null, callable $callRequest = null)
    {
        $reqData = new RequestData();
        $reqData->chatId = $this->chatId;
        $reqData->parseMode = 'markdown';
        $reqData->text = $this->getText()->get();

        $inlineKeyboardData = array_map(function($regional) use ($callButton) {
            $resultItem = [ 'text' => $regional['name'], 'callback_data' => null ];
            if(is_callable($callButton)) {
                $resultItem = $callButton($resultItem, $regional);
            }
            return [ $resultItem ];
        }, $this->regionals);

        $reqData->replyMarkup = new InlineKeyboard(...$inlineKeyboardData);
        $this->requestData = is_callable($callRequest) ? $callRequest($reqData) : $reqData;
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->requestData->build());
    }
}