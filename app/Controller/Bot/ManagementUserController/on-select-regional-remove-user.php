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

// Nasional was selected
if($regionalId == 'n') {

    $users = TelegramUser::query(function ($db, $table) {
        $tablePersUser = TelegramPersonalUser::$table;
        $query = "SELECT $table.*, $tablePersUser.nama AS full_name FROM $table LEFT JOIN $tablePersUser".
            " ON $tablePersUser.user_id=$table.id WHERE $table.level='nasional'";
        $data = $db->query($query);
        return $data ?? [];
    });

    $request = static::request('ManagementUser/TextRemoveUser');
    $request->setTarget( static::getRequestTarget() );
    $request->setUsers($users);
    $request->setLevelName('Nasional');
    $response = $request->send();
    
    if($response->isOk()) {
        $conversation = static::getRmUserConversation();
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

$callbackData = new CallbackData('mngusr.rmuserwit');
$request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
    return $inKeyboardItem;
});

return $request->send();