<?php

require __DIR__.'/../app/bootstrap.php';
use Longman\TelegramBot\Entities\Keyboard;
use App\Controller\BotController;

useHelper('telegram-callback');

$request1 = BotController::request('Registration/AnimationTou');
$request1->params->chatId = '23321';
$request1->params->replyMarkup = Keyboard::remove(['selective' => true]);
$req1Data = $request1->params->build();

$request2 = BotController::request('Registration/TextTou');
$request2->params->paste($request1->params->copy('parseMode', 'chatId', 'replyMarkup'));
$req2Data = $request2->params->build();

$request3 = BotController::request('Registration/SelectTouApproval');
$request3->params->paste($request1->params->copy('parseMode', 'chatId'));
$request3->setInKeyboard(function($inKeyboards) {
    $inKeyboards['approve']['callback_data'] = encodeCallbackData(
        'user.regist_approval',
        'Setuju',
        'agree'
    );
    $inKeyboards['reject']['callback_data'] = encodeCallbackData(
        'user.regist_approval',
        'Tidak',
        'disagree'
    );
    return $inKeyboards;
});
$req3Data = $request3->params->build();
dd($req3Data);