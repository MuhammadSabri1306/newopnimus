<?php
namespace App\Request;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use App\Core\RequestData;

use App\Model\Regional;
use App\Model\Witel;
use App\Model\RtuLocation;

class RequestInKeyboard
{

    public static function regionalList(RequestData $reqData, callable $callCalbackData)
    {
        $regionals = Regional::getSnameOrdered();
        $inlineKeyboardData = array_map(function($regional) use ($callCalbackData) {
            return [
                [ 'text' => $regional['name'], 'callback_data' => $callCalbackData($regional) ]
            ];
        }, $regionals);
        $reqData->replyMarkup = new InlineKeyboard(...$inlineKeyboardData);
        
        return Request::sendMessage($reqData->build());
    }

    public static function witelList($regionalId, RequestData $reqData, callable $callCalbackData)
    {
        $witels = Witel::getNameOrdered($regionalId);
        $inlineKeyboardData = array_map(function($witel) use ($callCalbackData) {
            return [
                [ 'text' => $witel['witel_name'], 'callback_data' => $callCalbackData($witel) ]
            ];
        }, $witels);
        $reqData->replyMarkup = new InlineKeyboard(...$inlineKeyboardData);
        
        return Request::sendMessage($reqData->build());
    }

    public static function locationList($witelId, RequestData $reqData, callable $callCalbackData)
    {
        $locations = RtuLocation::getSnameOrderedByWitel($witelId);
        $inlineKeyboardData = array_reduce($locations, function($result, $loc) use ($callCalbackData) {            
            $lastIndex = count($result) - 1;
            
            if($lastIndex < 0 || count($result[$lastIndex]) > 2) {
                array_push($result, []);
                $lastIndex++;
            }

            array_push($result[$lastIndex], [
                'text' => $loc['location_sname'],
                'callback_data' => $callCalbackData($loc)
            ]);

            return $result;
        }, []);

        $reqData->replyMarkup = new InlineKeyboard(...$inlineKeyboardData);
        return Request::sendMessage($reqData->build());
    }
}