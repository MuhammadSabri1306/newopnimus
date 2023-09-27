<?php
require __DIR__.'/../app/bootstrap.php';

use App\Controller\BotController;
use App\Model\TelegramUser;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\RtuLocation;
use App\Model\RtuList;
use App\ApiRequest\NewosaseApi;

useHelper('telegram-callback');

$chatId = $appConfig->userTesting->chatId;
$user = TelegramUser::findByChatId($chatId);

$request = BotController::request('Area/SelectLocation');
$questionText = $request->getText()->newLine()
    ->addItalic('* Anda juga dapat memilih RTU dan Port dengan mengetikkan perintah /cekrtu [Kode RTU], e.g: /cekrtu RTU00-D7-BAL')
    ->get();

$request->params->chatId = $chatId;
$request->params->text = $questionText;
$request->setData('locations', RtuLocation::getSnameOrderedByWitel($user['witel_id']));
$request->setInKeyboard(function($item, $loc) use ($questionText) {
    $item['callback_data'] = encodeCallbackData(
        'rtu.select_loc',
        $questionText,
        $item['text'],
        $loc
    );
    return $item;
});

dd_json($request->params->replyMarkup);