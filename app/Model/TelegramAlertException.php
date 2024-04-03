<?php
namespace App\Model;

use App\Core\Model;

class TelegramAlertException extends Model
{
    public static $table = 'telegram_alert_exception';

    public static function delete($id)
    {
        return static::query(function ($db, $table) use ($id) {
            return $db->delete($table, 'telegram_alert_exception_id=%i', $id);
        });
    }

    public static function deleteByChatId($chatId)
    {
        return static::query(function ($db, $table) use ($chatId) {
            return $db->delete($table, 'chat_id=%i', $chatId);
        });
    }
}