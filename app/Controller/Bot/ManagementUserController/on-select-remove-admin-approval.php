<?php

use App\Model\TelegramAdmin;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\Regional;
use App\Model\Witel;

$message = static::getMessage();
$messageId = $message->getMessageId();
$chatId = $message->getChat()->getId();

$response = static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();
if(!$adminId) {
    return $response;
}

$prevRequest = static::request('ManagementUser/SelectRemoveAdminApproval');
$adminData = TelegramAdmin::query(function ($db, $table) use ($adminId) {
    $tableUser = TelegramUser::$table;
    $tablePersUser = TelegramPersonalUser::$table;
    $query = "SELECT admin.*, pers.nama AS full_name, pers.nik, pers.is_organik FROM $table AS admin".
        " LEFT JOIN $tableUser AS user ON user.chat_id=admin.chat_id".
        " LEFT JOIN $tablePersUser AS pers ON pers.user_id=user.id".
        ' WHERE admin.id=%i';
    return $db->queryFirstRow($query, $adminId);
});
$prevRequest->setAdminData($adminData);
if($adminData['level'] != 'nasional') {
    $prevRequest->setRegional( Regional::find($adminData['regional_id']) );
}
if($adminData['level'] == 'witel') {
    $prevRequest->setWitel( Witel::find($adminData['witel_id']) );
}
$prevRequestText = $prevRequest->params->text;

TelegramAdmin::delete($adminId);

$request = static::request('TextDefault');
$request->setTarget( static::getRequestTarget() );
$request->setText(function($text) use ($prevRequestText) {
    return $text->addText($prevRequestText)->newLine(2)
        ->addText('Admin telah dihapus.');
});
return $request->send();