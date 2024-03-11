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

// Nasional was selected
if($regionalId == 'n') {

    $users = TelegramUser::query(function ($db, $table) {
        $tablePersUser = TelegramPersonalUser::$table;
        $query = "SELECT $table.*, $tablePersUser.nama AS full_name FROM $table LEFT JOIN $tablePersUser".
            " ON $tablePersUser.user_id=$table.id WHERE $table.level='nasional'";
        $data = $db->query($query);
        return $data ?? [];
    });

    $users = TelegramUser::query(function ($db, $table) {
        $tablePersUser = TelegramPersonalUser::$table;
        $query = "SELECT $table.*, $tablePersUser.nama AS full_name FROM $table LEFT JOIN $tablePersUser".
            " ON $tablePersUser.user_id=$table.id WHERE $table.is_pic=1 AND $table.level='nasional'";
        $users = $db->query($query);
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
    $request->setLevelName('Nasional');
    $response = $request->send();
    
    if($response->isOk() && count($users) > 0) {
        $conversation = static::getRmPicConversation();
        if(!$conversation->isExists()) $conversation->create();
        $conversation->userIds = array_map(fn($telgUser) => $telgUser['id'], $users);
        $conversation->commit();
    }
    return $response;

}

$request = static::request('Area/SelectWitel');
$request->setTarget( static::getRequestTarget() );

$regional = Regional::find($regionalId);
$witelOptions = [
    [ 'id' => 'r'.strval($regionalId), 'witel_name' => $regional['name'] ],
    ...Witel::getNameOrdered($regionalId)
];
$request->setWitels($witelOptions);

$callbackData = new CallbackData('mngusr.rmpictwit');
$request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
    return $inKeyboardItem;
});

return $request->send();