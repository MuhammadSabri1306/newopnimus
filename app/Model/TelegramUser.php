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
}