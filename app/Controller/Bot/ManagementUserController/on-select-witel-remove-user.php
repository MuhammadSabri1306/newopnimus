<?php

use App\Core\CallbackData;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;

$message = static::getMessage();
$messageId = $message->getMessageId();
$chatId = $message->getChat()->getId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

// Regional was selected
if(is_string($witelId) && $witelId[0] == 'r') {

    $regionalId = intval(substr($witelId, 1));
    $users = TelegramUser::query(function ($db, $table) use ($regionalId) {
        $tablePersUser = TelegramPersonalUser::$table;
        $query = "SELECT $table.*, $tablePersUser.nama AS full_name FROM $table LEFT JOIN $tablePersUser".
            " ON $tablePersUser.user_id=$table.id WHERE $table.level='regional' AND $table.regional_id=%i";
        $data = $db->query($query, $regionalId);
        return $data ?? [];
    });

    $request = static::request('ManagementUser/TextRemoveUser');
    $request->setTarget( static::getRequestTarget() );
    $request->setUsers($users);
    $request->setLevelName( Regional::find($regionalId)['name'] );
    $response = $request->send();

    if($response->isOk()) {
        $conversation = static::getRmUserConversation();
        if(!$conversation->isExists()) $conversation->create();
        $conversation->userIds = array_map(fn($telgUser) => $telgUser['id'], $users);
        $conversation->commit();
    }
    return $response;

}

// Witel was selected
$users = TelegramUser::query(function ($db, $table) use ($witelId) {
    $tablePersUser = TelegramPersonalUser::$table;
    $query = "SELECT $table.*, $tablePersUser.nama AS full_name FROM $table LEFT JOIN $tablePersUser".
        " ON $tablePersUser.user_id=$table.id WHERE $table.level='witel' AND $table.witel_id=%i";
    $data = $db->query($query, $witelId);
    return $data ?? [];
});

$request = static::request('ManagementUser/TextRemoveUser');
$request->setTarget( static::getRequestTarget() );
$request->setUsers($users);
$request->setLevelName( Witel::find($witelId)['witel_name'] );
$response = $request->send();

if($response->isOk()) {
    $conversation = static::getRmUserConversation();
    if(!$conversation->isExists()) $conversation->create();
    $conversation->userIds = array_map(fn($telgUser) => $telgUser['id'], $users);
    $conversation->commit();
}

return $response;