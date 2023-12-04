<?php
namespace App\Model;

use App\Core\Model;

class Conversation extends Model
{
    public static $table = 'conversation';

    public static function getActive($chatId, $userId)
    {
        $qParams = [ 'status' => 'active', 'chatId' => $chatId, 'userId' => $userId ];
        return Conversation::query(function ($db, $table) use ($qParams) {
            $query = "SELECT * FROM $table WHERE status=%s_status AND chat_id=%s_chatId AND user_id=%s_userId";
            return $db->query($query, $qParams) ?? null;
        });
    }

    public static function findActive($chatId, $userId)
    {
        $qParams = [ 'status' => 'active', 'chatId' => $chatId, 'userId' => $userId ];
        return Conversation::query(function ($db, $table) use ($qParams) {
            $query = "SELECT * FROM $table WHERE status=%s_status AND chat_id=%s_chatId AND user_id=%s_userId";
            return $db->queryFirstRow($query, $qParams) ?? null;
        });
    }
}