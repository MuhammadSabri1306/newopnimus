<?php

use App\Core\CallbackData;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\Regional;
use App\Model\Witel;

$conversation = static::getRmUserConversation();
$conversation->done();

$userIds = $conversation->userIds;
if(!is_array($userIds) || count($userIds) < 1 || static::getMessage()->getText() == '/user_management') {
    return null;
}

$messageText = trim(static::getMessage()->getText(true));
if(!preg_match('/^\d+$/', $messageText)) {
    return static::sendEmptyResponse();
}

$selectedNo = intval($messageText);
$maxUsersNo = count($userIds);
if($selectedNo < 0 && $selectedNo > $maxUsersNo) {
    
    $request = static::request('TextDefault');
    $request->setTarget( static::getRequestTarget() );
    $request->setText(function($text) use ($maxUsersNo) {
        return $text->addText("Input hanya dapat menerima angka dari 1 - $maxUsersNo.")->newLine()
            ->addItalic('\* Silahkan ketik nomor user yang akan dihapus.');
    });
    return $request->send();
}

$request = static::request('ManagementUser/SelectRemoveUserApproval');
$request->setTarget( static::getRequestTarget() );

$selectedUserId = $userIds[ $selectedNo - 1 ];
$telgUser = TelegramUser::find($selectedUserId);
$request->setTelegramUser($telgUser);

if($telgUser['type'] == 'private') {
    $request->setTelegramPersonalUser( TelegramPersonalUser::findByUserId($selectedUserId) );
}

if($telgUser['level'] != 'nasional') {
    $request->setRegional( Regional::find($telgUser['regional_id']) );
}

if($telgUser['level'] == 'witel') {
    $request->setWitel( Witel::find($telgUser['witel_id']) );
}

$callbackData = new CallbackData('mngusr.rmuserappr');
$request->setInKeyboard(function($inKeyboard) use ($callbackData, $selectedUserId) {
    $inKeyboard['approve']['callback_data'] = $callbackData->createEncodedData($selectedUserId);
    $inKeyboard['reject']['callback_data'] = $callbackData->createEncodedData(0);
    return $inKeyboard;
});

return $request->send();