<?php

use App\Core\CallbackData;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\TelegramAdmin;

$message = static::getMessage();
$messageId = $message->getMessageId();
$chatId = $message->getChat()->getId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

// Regional was selected
if(is_string($witelId) && $witelId[0] == 'r') {

    $regionalId = intval(substr($witelId, 1));
    $admins = TelegramAdmin::query(function ($db, $table) use ($regionalId) {
        $tableUser = TelegramUser::$table;
        $tablePersUser = TelegramPersonalUser::$table;
        $query = "SELECT admin.*, pers.nama AS full_name, pers.nik, pers.is_organik FROM $table AS admin".
            " LEFT JOIN $tableUser AS user ON user.chat_id=admin.chat_id".
            " LEFT JOIN $tablePersUser AS pers ON pers.user_id=user.id".
            ' WHERE admin.is_super_admin=0 AND admin.level=\'regional\' AND admin.regional_id=%i';
        return $db->query($query, $regionalId);
    });
    
    $request = static::request('ManagementUser/TextRemoveAdmin');
    $request->setTarget( static::getRequestTarget() );
    $request->setAdmins($admins);
    $request->setLevelName( Regional::find($regionalId)['name'] );
    $response = $request->send();
    
    if($response->isOk() && count($admins) > 0) {
        $conversation = static::getRmAdminConversation();
        if(!$conversation->isExists()) $conversation->create();
        $conversation->adminIds = array_map(fn($item) => $item['id'], $admins);
        $conversation->commit();
    }
    
    return $response;

}

// Witel was selected
$admins = TelegramAdmin::query(function ($db, $table) use ($witelId) {
    $tableUser = TelegramUser::$table;
    $tablePersUser = TelegramPersonalUser::$table;
    $query = "SELECT admin.*, pers.nama AS full_name, pers.nik, pers.is_organik FROM $table AS admin".
        " LEFT JOIN $tableUser AS user ON user.chat_id=admin.chat_id".
        " LEFT JOIN $tablePersUser AS pers ON pers.user_id=user.id".
        ' WHERE admin.is_super_admin=0 AND admin.level=\'witel\' AND admin.witel_id=%i';
    return $db->query($query, $witelId);
});

$request = static::request('ManagementUser/TextRemoveAdmin');
$request->setTarget( static::getRequestTarget() );
$request->setAdmins($admins);
$request->setLevelName( Witel::find($witelId)['witel_name'] );
$response = $request->send();

if($response->isOk() && count($admins) > 0) {
    $conversation = static::getRmAdminConversation();
    if(!$conversation->isExists()) $conversation->create();
    $conversation->adminIds = array_map(fn($item) => $item['id'], $admins);
    $conversation->commit();
}

return $response;