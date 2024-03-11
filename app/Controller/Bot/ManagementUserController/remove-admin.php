<?php

use App\Core\CallbackData;
use App\Model\TelegramAdmin;
use App\Model\TelegramUser;
use App\Model\TelegramPersonalUser;
use App\Model\Regional;
use App\Model\Witel;

$conversation = static::getRmAdminConversation();
$conversation->done();

$adminIds = $conversation->adminIds;
if(!is_array($adminIds) || count($adminIds) < 1 || static::getMessage()->getText() == '/user_management') {
    return null;
}

$messageText = trim(static::getMessage()->getText(true));
if(!preg_match('/^\d+$/', $messageText)) {
    return static::sendEmptyResponse();
}

$selectedNo = intval($messageText);
$maxAdminsNo = count($adminIds);
if($selectedNo < 0 && $selectedNo > $maxAdminsNo) {
    
    $request = static::request('TextDefault');
    $request->setTarget( static::getRequestTarget() );
    $request->setText(function($text) use ($maxAdminsNo) {
        return $text->addText("Input hanya dapat menerima angka dari 1 - $maxAdminsNo.")->newLine()
            ->addItalic('\* Silahkan ketik nomor Admin yang akan dihapus.');
    });
    return $request->send();

}

$request = static::request('ManagementUser/SelectRemoveAdminApproval');
$request->setTarget( static::getRequestTarget() );

$selectedAdminId = $adminIds[ $selectedNo - 1 ];
$adminData = TelegramAdmin::query(function ($db, $table) use ($selectedAdminId) {
    $tableUser = TelegramUser::$table;
    $tablePersUser = TelegramPersonalUser::$table;
    $query = 'SELECT admin.*, pers.nama AS full_name, pers.nik, pers.is_organik, pers.instansi, pers.unit'.
        " FROM $table AS admin".
        " LEFT JOIN $tableUser AS user ON user.chat_id=admin.chat_id".
        " LEFT JOIN $tablePersUser AS pers ON pers.user_id=user.id".
        ' WHERE admin.id=%i';
    return $db->queryFirstRow($query, $selectedAdminId);
});
$request->setAdminData($adminData);

if($adminData['level'] != 'nasional') {
    $request->setRegional( Regional::find($adminData['regional_id']) );
}

if($adminData['level'] == 'witel') {
    $request->setWitel( Witel::find($adminData['witel_id']) );
}

$callbackData = new CallbackData('mngusr.rmadminappr');
$request->setInKeyboard(function($inKeyboard) use ($callbackData, $selectedAdminId) {
    $inKeyboard['approve']['callback_data'] = $callbackData->createEncodedData($selectedAdminId);
    $inKeyboard['reject']['callback_data'] = $callbackData->createEncodedData(0);
    return $inKeyboard;
});

return $request->send();