<?php

use App\Core\CallbackData;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\PicLocation;

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
            " ON $tablePersUser.user_id=$table.id WHERE $table.is_pic=1 AND $table.level='regional' AND $table.regional_id=%i";
        $users = $db->query($query, $regionalId);
        if(count($users) < 1) return $users;
    
        $userIds = array_map(fn($telgUser) => $telgUser['id'], $users);
        $picLocs = PicLocation::getByUsers($userIds);
        foreach($users as $i => $user) {
            $users[$i]['loc_snames'] = [];
            foreach($picLocs as $picLoc) {
                if($picLoc['user_id'] == $user['id']) {
                    array_push($users[$i]['loc_snames'], $picLoc['location_sname']);
                }
            }
        }
        return $users;
    });

    $request = static::request('ManagementUser/TextRemovePic');
    $request->setTarget( static::getRequestTarget() );
    $request->setUsers($users);
    $request->setLevelName( Regional::find($regionalId)['name'] );
    $response = $request->send();

    if($response->isOk() && count($users) > 0) {
        $conversation = static::getRmPicConversation();
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
        " ON $tablePersUser.user_id=$table.id WHERE $table.is_pic=1 AND $table.level='witel' AND $table.witel_id=%i";
    $users = $db->query($query, $witelId);
    if(count($users) < 1) return $users;

    $userIds = array_map(fn($telgUser) => $telgUser['id'], $users);
    $picLocs = PicLocation::getByUsers($userIds);
    foreach($users as $i => $user) {
        $users[$i]['loc_snames'] = [];
        foreach($picLocs as $picLoc) {
            if($picLoc['user_id'] == $user['id']) {
                array_push($users[$i]['loc_snames'], $picLoc['location_sname']);
            }
        }
    }
    return $users;
});

$request = static::request('ManagementUser/TextRemovePic');
$request->setTarget( static::getRequestTarget() );
$request->setUsers($users);
$request->setLevelName( Witel::find($witelId)['witel_name'] );
$response = $request->send();

if($response->isOk() && count($users) > 0) {
    $conversation = static::getRmPicConversation();
    if(!$conversation->isExists()) $conversation->create();
    $conversation->userIds = array_map(fn($telgUser) => $telgUser['id'], $users);
    $conversation->commit();
}

return $response;