<?php

use App\Core\CallbackData;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\TelegramAdmin;

$message = static::getMessage();
$messageId = $message->getMessageId();
$chatId = $message->getChat()->getId();

static::request('Action/DeleteMessage', [ $messageId, $chatId ])->send();

// Nasional was selected
if($regionalId == 'n') {

    $admins = TelegramAdmin::query(function ($db, $table) {
        $tableUser = TelegramUser::$table;
        $tablePersUser = TelegramPersonalUser::$table;
        $query = "SELECT admin.*, pers.nama AS full_name, pers.nik, pers.is_organik FROM $table AS admin".
            " LEFT JOIN $tableUser AS user ON user.chat_id=admin.chat_id".
            " LEFT JOIN $tablePersUser AS pers ON pers.user_id=user.id".
            ' WHERE admin.is_super_admin=0 AND admin.level=\'nasional\'';
        return $db->query($query);
    });
    
    $request = static::request('ManagementUser/TextRemoveAdmin');
    $request->setTarget( static::getRequestTarget() );
    $request->setAdmins($admins);
    $request->setLevelName('Nasional');
    $response = $request->send();
    
    if($response->isOk() && count($admins) > 0) {
        $conversation = static::getRmAdminConversation();
        if(!$conversation->isExists()) $conversation->create();
        $conversation->adminIds = array_map(fn($item) => $item['id'], $admins);
        $conversation->commit();
    }
    
    return $response;

}

$request = static::request('Area/SelectWitel');
$request->setTarget( static::getRequestTarget() );

$witelOptions = Witel::getNameOrdered($regionalId);
if(static::getAdmin()['level'] == 'nasional') {
    $regional = Regional::find($regionalId);
    array_unshift($witelOptions, [ 'id' => 'r'.strval($regionalId), 'witel_name' => $regional['name'] ]);
}
$request->setWitels($witelOptions);

$callbackData = new CallbackData('mngusr.rmadminwit');
$request->setInKeyboard(function($inKeyboardItem, $witel) use ($callbackData) {
    $inKeyboardItem['callback_data'] = $callbackData->createEncodedData($witel['id']);
    return $inKeyboardItem;
});

return $request->send();