<?php
namespace App\Model;

use App\Core\Model;
use App\Model\PicLocation;

class TelegramUser extends Model
{
    public static $table = 'telegram_user';
    
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

        return TelegramUser::query(function ($db, $table) use ($data) {
            $db->insert($table, $data);
            $id = $db->insertId();
            return $id ? TelegramUser::find($id) : null;
        });
    }

    public static function find($id)
    {
        return TelegramUser::query(function ($db, $table) use ($id) {
            $user = $db->queryFirstRow("SELECT * FROM $table WHERE id=%i", $id);
            if(!$user) return null;

            $user['locations'] = [];
            if(boolval($user['is_pic'])) {
                $user['locations'] = PicLocation::getByUser($user['id']);
            }
            
            return $user;
        });
    }

    public static function findByChatId($chatId)
    {
        return TelegramUser::query(function ($db, $table) use ($chatId) {
            $user = $db->queryFirstRow("SELECT * FROM $table WHERE chat_id=%s", $chatId);
            if(!$user) return null;

            if(boolval($user['is_pic'])) {
                $user['locations'] = PicLocation::getByUser($user['id']);
            } else {
                $user['locations'] = [];
            }
            
            return $user;
        });
    }

    public static function delete($id)
    {
        return TelegramUser::query(function ($db, $table) use ($id) {
            return $db->delete($table, 'id=%i', $id);
        });
    }

    public static function deleteByChatId($chatId)
    {
        return TelegramUser::query(function ($db, $table) use ($chatId) {
            return $db->delete($table, 'chat_id=%s', $chatId);
        });
    }

    public static function update($id, array $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return TelegramUser::query(function ($db, $table) use ($id, $data) {
            return $db->update($table, $data, "id=%i", $id);
        });
    }
}