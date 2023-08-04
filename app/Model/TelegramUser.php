<?php
namespace App\Model;

use App\Core\Model;

class TelegramUser extends Model
{
    protected static $table = 'telegram_user';
    
    public static function exists($chatId)
    {
        $count = TelegramUser::query(function ($db, $table) use ($chatId) {
            return $db->queryFirstField("SELECT COUNT(*) FROM $table WHERE chat_id=%s", $chatId);
        });

        return $count > 0; 
    }

    public static function create(array $data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');

        $id = TelegramUser::query(function ($db, $table) use ($data) {
            $db->insert($table, $data);
            return $db->insertId();
        });
        return $id ? true : false;
    }
}