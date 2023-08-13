<?php
namespace App\Model;

use App\Core\Model;

class TelegramPersonalUser extends Model
{
    public static $table = 'telegram_personal_user';

    public static function create(array $data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');

        return TelegramPersonalUser::query(function ($db, $table) use ($data) {
            $db->insert($table, $data);
            $id = $db->insertId();
            return $id ? TelegramPersonalUser::find($id) : null;
        });
    }

    public static function find($id)
    {
        return TelegramPersonalUser::query(function ($db, $table) use ($id) {
            return $db->queryFirstRow("SELECT * FROM $table WHERE id=%i", $id);
        });
    }

    public static function delete($id)
    {
        return TelegramPersonalUser::query(function ($db, $table) use ($id) {
            return $db->delete($table, 'id=%i', $id);
        });
    }

    public static function deleteByUserId($userId)
    {
        return TelegramPersonalUser::query(function ($db, $table) use ($userId) {
            return $db->delete($table, 'user_id=%i', $userId);
        });
    }
}