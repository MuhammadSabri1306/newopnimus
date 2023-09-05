<?php
namespace App\TelegramResponse\Registration;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\TelegramResponse;
use App\Core\RequestData;
use App\Core\TelegramText;

class PicTou extends TelegramResponse
{
    private $chatId;
    private $requestData;
    private $telgUser;

    public function __construct($chatId, array $telgUser)
    {
        $this->chatId = $chatId;
        $this->telgUser = $telgUser;
        $this->setRequest();
    }

    public function getText()
    {
        $user = $this->telgUser;
        $text = TelegramText::create();

        if(!$user['is_pic']) {
            $text->addText('Anda akan mendaftarkan diri anda menjadi PIC Lokasi. ')
                ->addText('Silahkan memanfaatkan fitur ini apabila anda merupakan pengawal perangkat Network Element Telkom Indonesia di lokasi tertentu.')
                ->newLine(2);
        }

        if(count($user['locations']) < 1) {
            $text->startItalic()->addText('Saat ini anda belum menjadi PIC di lokasi manapun.')->endItalic()->newLine(2);
        } else {
            foreach($user['locations'] as $loc) {
                $locName = $loc['location_name'];
                $locSname = $loc['location_sname'];
                $text->startItalic()
                    ->startBold()->addText("- $locName ($locSname)")->endBold()
                    ->endItalic()->newLine(2);
            }
            $text->newLine();
        }

        $text->addText('Dengan mendaftarkan diri anda sebagai PIC lokasi, anda akan mendapatkan:')->newLine()
            ->addText('📌 ')->startBold()->addText('Alert khusus di lokasi yang anda kawal via japrian OPNIMUS, dan')->endBold()->newLine()
            ->addText('📌 ')->startBold()->addText('Tagging nama anda di grup agar tidak ada alarm yang terlewat dan memudahkan respon.')->endBold()->newLine();
        
        return $text;
    }

    public function setRequest(callable $callButton = null, callable $callRequest = null)
    {
        $reqData = new RequestData();
        $reqData->chatId = $this->chatId;
        $reqData->parseMode = 'markdown';
        $reqData->text = $this->getText()->get();

        $inlineKeyboardData = [
            'agree' => ['text' => 'Lanjutkan', 'callback_data' => null],
            'disagree' => ['text' => 'Batalkan', 'callback_data' => null]
        ];

        if(is_callable($callButton)) {
            $inlineKeyboardData = $callButton($inlineKeyboardData);
        }

        $reqData->replyMarkup = new InlineKeyboard([
            $inlineKeyboardData['agree'],
            $inlineKeyboardData['disagree']
        ]);

        $this->requestData = is_callable($callRequest) ? $callRequest($reqData) : $reqData;
    }

    public function send(): ServerResponse
    {
        return Request::sendMessage($this->requestData->build());
    }
}