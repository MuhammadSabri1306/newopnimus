<?php
require __DIR__.'/../app/bootstrap.php';

use Longman\TelegramBot\Request;
use App\Model\RtuPortStatus;
use App\Model\TelegramUser;
use App\Core\RequestData;
use App\BuiltMessageText\AlarmText;
use App\Controller\BotController;

$reqData = New RequestData();
$reqData->parseMode = 'markdown';
$reqData->chatId = 1931357638;

$user = TelegramUser::findPicByChatId(1931357638);
$ports = RtuPortStatus::getExistsAlarm([ 'witel' => $user['witel_id'] ]);
$reqData->text = AlarmText::witelAlarmText($user['witel_id'], $ports)->get();
// Request::sendMessage($reqData->build());
dd($reqData->build());