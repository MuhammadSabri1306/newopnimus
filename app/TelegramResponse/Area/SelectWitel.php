<?php
namespace App\TelegramResponse\Area;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramResponse;
use App\Core\RequestData;
use App\Core\TelegramText;

use App\Model\Witel;

class SelectWitel extends TelegramResponse
{
    private $chatId;
    private $requestData;
    private $witels;
    private $regionalId;

    public function __construct($chatId, $regionalId)
    {
        $this->chatId = $chatId;
        $this->regionalId = $regionalId;
        $this->witels = Witel::getNameOrdered($regionalId);
        $this->setRequest();
    }

    public function getText()
    {
        return TelegramText::create('Silahkan pilih ')
            ->addBold('Witel')
            ->addText('.');
    }

    public function setRequest(callable $callButton = null, callable $callRequest = null)
    {
        $reqData = new RequestData();
        $reqData->chatId = $this->chatId;
        $reqData->parseMode = 'markdown';
        $reqData->text = $this->getText()->get();

        $inlineKeyboardData = array_map(function($witel) use ($callButton) {
            $resultItem = [ 'text' => $witel['witel_name'], 'callback_data' => null ];
            if(is_callable($callButton)) {
                $resultItem = $callButton($resultItem, $witel);
            }
            return [ $resultItem ];
        }, $this->witels);

        $reqData->replyMarkup = new InlineKeyboard(...$inlineKeyboardData);
        $this->requestData = is_callable($callRequest) ? $callRequest($reqData) : $reqData;
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->requestData->build());
    }
}