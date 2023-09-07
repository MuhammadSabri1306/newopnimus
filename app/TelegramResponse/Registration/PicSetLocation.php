<?php
namespace App\TelegramResponse\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramResponse;
use App\Core\RequestData;
use App\Core\TelegramText;

use App\Model\RtuLocation;

class PicSetLocation extends TelegramResponse
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
        $text = TelegramText::create('Berikut adalah lokasi yang akan dimonitor:')->newLine(2);
        foreach($this->locations as $loc) {

            $locName = $loc['location_name'];
            $locSname = $loc['location_sname'];
            $text->addItalic("- $locName ($locSname)")->newLine(2);

        }
        $text->addItalic('Anda dapat memilih maksimal 3 lokasi.');
        return $text;
    }

    public function setRequest(callable $callButton = null, callable $callRequest = null)
    {
        $reqData = new RequestData();
        $reqData->chatId = $this->chatId;
        $reqData->parseMode = 'markdown';
        $reqData->text = $this->getText()->get();

        $inlineKeyboardParams = [
            'next' => ['text' => 'Lanjutkan', 'callback_data' => 'pic.update_loc.next'],
            'add' => [ 'text' => 'Tambah', 'callback_data' => 'pic.update_loc.add' ],
            'remove' => [ 'text' => 'Hapus', 'callback_data' => 'pic.update_loc.remove' ],
        ];

        if(is_callable($callButton)) {
            $inlineKeyboardParams = $callButton($inlineKeyboardParams);
        }

        $inlineKeyboardData = [
            [],
            [ $inlineKeyboardParams['next'] ]
        ];

        if(count($this->locations) > 1) {
            array_push($inlineKeyboardData[0], $inlineKeyboardParams['remove']);
        }
        
        if(count($this->locations) < 3) {
            array_push($inlineKeyboardData[0], $inlineKeyboardParams['add']);
        }

        $reqData->replyMarkup = new InlineKeyboard(...$inlineKeyboardData);
        $this->requestData = is_callable($callRequest) ? $callRequest($reqData) : $reqData;
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->requestData->build());
    }
}