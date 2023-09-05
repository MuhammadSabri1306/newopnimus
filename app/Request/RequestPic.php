<?php
namespace App\Request;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\RequestData;

use App\Model\RtuLocation;
use App\BuiltMessageText\PicText;
use App\Controller\BotController;

class RequestPic
{

    public static function manageLocation(array $locIds, callable $callReqData, callable $callCalbackData)
    {
        $picLocs = RtuLocation::getByIds($locIds);

        $reqData = new RequestData();
        $reqData->parseMode = 'markdown';

        $reqData->text = PicText::editSelectedLocation($picLocs)->get();
        
        $inlineKeyboardParams = $callCalbackData([
            'next' => ['text' => 'Lanjutkan', 'callback_data' => 'pic.update_loc.next'],
            'add' => [ 'text' => 'Tambah', 'callback_data' => 'pic.update_loc.add' ],
            'remove' => [ 'text' => 'Hapus', 'callback_data' => 'pic.update_loc.remove' ],
        ]);

        $inlineKeyboardData = [
            [],
            [ $inlineKeyboardParams['next'] ]
        ];

        if(count($picLocs) > 1) {
            array_push($inlineKeyboardData[0], $inlineKeyboardParams['remove']);
        }
        
        if(count($picLocs) < 3) {
            array_push($inlineKeyboardData[0], $inlineKeyboardParams['add']);
        }

        $reqData->replyMarkup = new InlineKeyboard(...$inlineKeyboardData);
        $reqData1 = $callReqData($reqData);

        return Request::sendMessage($reqData1->build());
    }
}