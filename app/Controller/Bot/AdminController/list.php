<?php

use App\Model\TelegramAdmin;

$message = static::$command->getMessage();
$chatId = $message->getChat()->getId();

$telgAdmin = TelegramAdmin::findByChatId($chatId);
if($telgAdmin['is_super_admin'] != 1) {
    return static::sendEmptyResponse();
}

$admins = TelegramAdmin::query(function($db, $table) {
    $query = 'SELECT admin.*, treg.name AS regional_name, witel.witel_name'.
        ' FROM telegram_admin AS admin'.
        ' LEFT JOIN regional AS treg ON treg.id=admin.regional_id'.
        ' LEFT JOIN witel AS witel ON witel.id=admin.witel_id'.
        ' WHERE is_super_admin=0'.
        ' ORDER BY treg.divre_code, witel.witel_name, admin.created_at';
    return $db->query($query);
});

$request = static::request('User/TextAdminList');
$request->params->chatId = $chatId;
$request->setAdmins($admins);
return $request->send();