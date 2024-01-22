<?php
namespace App\Controller\Bot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\ChatAction;
use App\Core\RequestData;
use App\Core\TelegramText;
use App\Core\CallbackData;
use App\Controller\BotController;
use App\Model\TelegramUser;
use App\Model\Regional;
use App\Model\Witel;
use App\BuiltMessageText\UserText;
use App\BuiltMessageText\AlarmText;
use App\ApiRequest\NewosaseApi;
use App\Request\RequestInKeyboard;

class AlarmController extends BotController
{
    public static $callbacks = [
        'alarm.select_regional' => 'onSelectRegional',
        'alarm.reg' => 'onSelectRegionalV2',
        'alarm.wit' => 'onSelectWitel'
    ];

    public static function checkExistAlarm()
    {
        $message = AlarmController::$command->getMessage();

        $reqData = New RequestData();
        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();

        $user = TelegramUser::findByChatId($reqData->chatId);
        if(!$user) {
            $reqData->text = UserText::unregistedText()->get();
            return Request::sendMessage($reqData->build());
        }

        $reqDataTyping = $reqData->duplicate('chatId');
        $reqDataTyping->action = ChatAction::TYPING;
        Request::sendChatAction($reqDataTyping->build());

        if($user['level'] == 'nasional') {
            $reqData->text = 'Silahkan pilih Regional.';
            return RequestInKeyboard::regionalList(
                $reqData,
                fn($regional) => 'alarm.select_regional.'.$regional['id']
            );
        }

        $newosaseApi = new NewosaseApi();
        $newosaseApi->request['query'] = [ 'isAlert' => 1 ];
        if($user['level'] == 'regional') {
            $newosaseApi->request['query']['regionalId'] = $user['regional_id'];
        } elseif($user['level'] == 'witel') {
            $newosaseApi->request['query']['witelId'] = $user['witel_id'];
        }

        $fetResp = $newosaseApi->sendRequest('GET', '/dashboard-service/dashboard/rtu/port-sensors');
        if(!$fetResp) {
            $reqData->text = 'Terjadi masalah saat menghubungi server.';
            return Request::sendMessage($reqData->build());
        }

        $ports = array_filter($fetResp->result->payload, function($port) {
            return $port->no_port != 'many';
        });

        if(!$ports || count($ports) < 1) {

            $request = static::request('TextDefault');
            $request->params->chatId = $chatId;

            $levelName = null;
            if($user['level'] == 'nasional') {
                $levelName = 'level Nasional';
            } elseif($user['level'] == 'regional') {
                $regional = Regional::find($user['regional_id']);
                $levelName = $regional ? $regional['name'] : null;
            } elseif($user['level'] == 'witel') {
                $witel = Witel::find($user['witel_id']);
                $levelName = $witel ? $witel['witel_name'] : null;
            }

            $request->setText(function($text) use ($levelName) {
                $text->addText('✅✅')->addBold('ZERO ALARM')->addText('✅✅')->newLine()
                    ->addText('Saat ini tidak ada alarm');
                if($levelName) $text->addText(' di ')->addBold($levelName);
                $text->addText(' pada ')->addBold( date('Y-m-d H:i:s') )->newLine()
                    ->startInlineCode()
                    ->addText('Tetap Waspada dan disiplin mengawal Network Element Kita.')
                    ->addText(' Semoga Network Element Kita tetap dalam kondisi prima dan terkawal.')
                    ->endInlineCode()->newLine(2)
                    ->addText('Ketikan /help untuk mengakses menu OPNIMUS lainnya.');
                return $text;
            });
            return $request->send();
        }

        if($user['level'] == 'regional') {
            $regionalAlarmText = AlarmText::regionalAlarmText1($user['regional_id'], $ports)->getSplittedByLine(30);
            $textList = array_map(fn($textItem) => htmlspecialchars($textItem), $regionalAlarmText);
            return BotController::sendMessageList($reqData, $textList);
        }
        
        if($user['level'] == 'witel') {
            $witelAlarmText = AlarmText::witelAlarmText1($user['witel_id'], $ports)->getSplittedByLine(30);
            $textList = array_map(fn($textItem) => htmlspecialchars($textItem), $witelAlarmText);
            return BotController::sendMessageList($reqData, $textList, true);
        }
        
        // PIC
        return Request::emptyMessage();
    }

    public static function onSelectRegional($regionalId, $callbackQuery)
    {
        $reqData = New RequestData();
        $message = $callbackQuery->getMessage();
        $user = $callbackQuery->getFrom();
        $regional = Regional::find($regionalId);

        $reqData->parseMode = 'markdown';
        $reqData->chatId = $message->getChat()->getId();
        $reqData->messageId = $message->getMessageId();
        $reqData->text = TelegramText::create('Silahkan pilih Regional.')->newLine(2)
            ->addBold('=> ')->addText($regional['name'])
            ->get();
        Request::editMessageText($reqData->build());

        $regionalAlarmText = AlarmText::regionalAlarmText1($regionalId, $ports)->getSplittedByLine(30);
        $textList = array_map(fn($textItem) => htmlspecialchars($textItem), $regionalAlarmText);
        return BotController::sendMessageList($reqData->duplicate('parseMode', 'chatId'), $textList);
    }

    public static function checkExistAlarmV2()
    {
        $message = AlarmController::$command->getMessage();
        $chatId = $message->getChat()->getId();

        $user = TelegramUser::findByChatId($chatId);
        if(!$user) {
            
            $request = BotController::request('Error/TextUserUnidentified');
            $request->params->chatId = $chatId;
            return $request->send();

        }

        if($user['level'] == 'nasional') {
            
            $request = BotController::request('Area/SelectRegional');
            $request->params->chatId = $chatId;
            $request->setData('regionals', Regional::getSnameOrdered());

            $callbackData = new CallbackData('alarm.reg');
            $request->setInKeyboard(function($inlineKeyboardItem, $regional) use ($callbackData) {
                $inlineKeyboardItem['callback_data'] = $callbackData->createEncodedData($regional['id']);
                return $inlineKeyboardItem;
            });

            return $request->send();

        }

        if($user['level'] == 'regional') {

            $request = BotController::request('Area/SelectWitel');
            $request->params->chatId = $chatId;
            $request->setData('witels', Witel::getNameOrdered($user['regional_id']));
            
            $callbackData = new CallbackData('alarm.wit');
            $request->setInKeyboard(function($inlineKeyboardItem, $witel) use ($callbackData) {
                $inlineKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
                return $inlineKeyboardItem;
            });

            return $request->send();

        }

        $request = BotController::request('Action/Typing');
        $request->params->chatId = $chatId;
        $request->send();

        $newosaseApi = new NewosaseApi();
        $newosaseApi->request['query'] = [ 'isAlert' => 1 ];
        if($user['level'] == 'regional') {
            $newosaseApi->request['query']['regionalId'] = $user['regional_id'];
        } elseif($user['level'] == 'witel') {
            $newosaseApi->request['query']['witelId'] = $user['witel_id'];
        }

        $fetResp = $newosaseApi->sendRequest('GET', '/dashboard-service/dashboard/rtu/port-sensors');
        if(!$fetResp) {
            
            $request = BotController::request('TextDefault');
            $request->params->chatId = $chatId;
            $request->setText(fn($text) => $text->addText('Terjadi masalah saat menghubungi server.'));
            return $request->send();

        }

        $ports = array_filter($fetResp->result->payload, function($port) {
            return $port->no_port != 'many';
        });

        if(!$ports || count($ports) < 1) {
            
            $request = static::request('TextDefault');
            $request->params->chatId = $chatId;

            $levelName = null;
            if($user['level'] == 'nasional') {
                $levelName = 'level Nasional';
            } elseif($user['level'] == 'regional') {
                $regional = Regional::find($user['regional_id']);
                $levelName = $regional ? $regional['name'] : null;
            } elseif($user['level'] == 'witel') {
                $witel = Witel::find($user['witel_id']);
                $levelName = $witel ? $witel['witel_name'] : null;
            }

            $request->setText(function($text) use ($levelName) {
                $text->addText('✅✅')->addBold('ZERO ALARM')->addText('✅✅')->newLine()
                    ->addText('Saat ini tidak ada alarm');
                if($levelName) $text->addText(' di ')->addBold($levelName);
                $text->addText(' pada ')->addBold( date('Y-m-d H:i:s') )->newLine()
                    ->startInlineCode()
                    ->addText('Tetap Waspada dan disiplin mengawal Network Element Kita.')
                    ->addText(' Semoga Network Element Kita tetap dalam kondisi prima dan terkawal.')
                    ->endInlineCode()->newLine(2)
                    ->addText('Ketikan /help untuk mengakses menu OPNIMUS lainnya.');
                return $text;
            });
            return $request->send();

        }

        $request = BotController::request('Alarm/TextPortWitel');
        $request->params->chatId = $chatId;
        $request->setWitel(Witel::find($user['witel_id']));
        $request->setPorts($ports);
        return $request->send();
    }

    public static function onSelectRegionalV2($callbackValue, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $request = BotController::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();

        $request = BotController::request('Area/SelectWitel');
        $request->params->chatId = $chatId;
        $request->setData('witels', Witel::getNameOrdered($callbackValue));
        
        $callbackData = new CallbackData('alarm.wit');
        $request->setInKeyboard(function($inlineKeyboardItem, $witel) use ($callbackData) {
            $inlineKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
            return $inlineKeyboardItem;
        });

        return $request->send();
    }

    public static function onSelectWitel($callbackValue, $callbackQuery)
    {
        $message = $callbackQuery->getMessage();
        $messageId = $message->getMessageId();
        $chatId = $message->getChat()->getId();

        $request = BotController::request('Action/DeleteMessage', [ $messageId, $chatId ]);
        $request->send();

        $request = BotController::request('Action/Typing');
        $request->params->chatId = $chatId;
        $request->send();

        $newosaseApi = new NewosaseApi();
        $newosaseApi->request['query'] = [
            'isAlert' => 1,
            'witelId' => $callbackValue
        ];

        $fetResp = $newosaseApi->sendRequest('GET', '/dashboard-service/dashboard/rtu/port-sensors');
        if(!$fetResp) {

            $request = BotController::request('TextDefault');
            $request->params->chatId = $chatId;
            $request->setText(fn($text) => $text->addText('Terjadi masalah saat menghubungi server.'));
            return $request->send();

        }

        $ports = array_filter($fetResp->result->payload, function($port) {
            return $port->no_port != 'many';
        });

        if(!$ports || count($ports) < 1) {
            
            $request = static::request('TextDefault');
            $request->params->chatId = $chatId;

            $levelName = null;
            if($user['level'] == 'nasional') {
                $levelName = 'level Nasional';
            } elseif($user['level'] == 'regional') {
                $regional = Regional::find($user['regional_id']);
                $levelName = $regional ? $regional['name'] : null;
            } elseif($user['level'] == 'witel') {
                $witel = Witel::find($user['witel_id']);
                $levelName = $witel ? $witel['witel_name'] : null;
            }

            $request->setText(function($text) use ($levelName) {
                $text->addText('✅✅')->addBold('ZERO ALARM')->addText('✅✅')->newLine()
                    ->addText('Saat ini tidak ada alarm');
                if($levelName) $text->addText(' di ')->addBold($levelName);
                $text->addText(' pada ')->addBold( date('Y-m-d H:i:s') )->newLine()
                    ->startInlineCode()
                    ->addText('Tetap Waspada dan disiplin mengawal Network Element Kita.')
                    ->addText(' Semoga Network Element Kita tetap dalam kondisi prima dan terkawal.')
                    ->endInlineCode()->newLine(2)
                    ->addText('Ketikan /help untuk mengakses menu OPNIMUS lainnya.');
                return $text;
            });
            return $request->send();

        }

        $request = BotController::request('Alarm/TextPortWitel');
        $request->params->chatId = $chatId;
        $request->setWitel(Witel::find($callbackValue));
        $request->setPorts($ports);
        return $request->send();
    }
}