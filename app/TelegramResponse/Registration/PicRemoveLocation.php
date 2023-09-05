<?php
namespace App\TelegramResponse\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramResponse;
use App\Core\RequestData;
use App\Core\TelegramText;

use App\Model\RtuLocation;

class PicRemoveLocation extends TelegramResponse
{
    private $chatId;
    private $requestData;
    private $locations;

    public function __construct($chatId, array $locIds)
    {
        $this->chatId = $chatId;
        $this->locations = RtuLocation::getByIds($locIds);
        $this->setRequest();
    }

    public function getText()
    {
        return TelegramText::create('Silahkan pilih ')
            ->addBold('Lokasi')
            ->addText(' yang ingin dihapus.');
        return $text;
    }

    public function setRequest(callable $callButton = null, callable $callRequest = null)
    {
        $reqData = new RequestData();
        $reqData->chatId = $this->chatId;
        $reqData->parseMode = 'markdown';
        $reqData->text = $this->getText()->get();

        $inlineKeyboardData = array_reduce($this->locations, function($result, $loc) use ($callButton) {            
            
            $lastIndex = count($result) - 1;
            if($lastIndex < 0 || count($result[$lastIndex]) > 2) {
                array_push($result, []);
                $lastIndex++;
            }

            $resultItem = [ 'text' => '❌ '.$loc['location_sname'], 'callback_data' => null ];
            if(is_callable($callButton)) {
                $resultItem = $callButton($resultItem, $loc);
            }

            array_push($result[$lastIndex], $resultItem);
            return $result;

        }, []);

        $reqData->replyMarkup = new InlineKeyboard(...$inlineKeyboardData);
        $this->requestData = is_callable($callRequest) ? $callRequest($reqData) : $reqData;
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->requestData->build());
    }
}