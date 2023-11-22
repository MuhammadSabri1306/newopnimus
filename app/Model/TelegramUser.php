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

    public static function findByRegistId($registId)
    {
        return TelegramUser::query(function ($db, $table) use ($registId) {
            $user = $db->queryFirstRow("SELECT * FROM $table WHERE regist_id=%i", $registId);
            if(!$user) return null;

            $user['locations'] = [];
            if(boolval($user['is_pic'])) {
                $user['locations'] = PicLocation::getByUser($user['id']);
            }
            
            return $user;
        });
    }

    public static function findAlertWitelGroup($witelId)
    {
        return TelegramUser::query(function ($db, $table) use ($witelId) {
            $group = $db->queryFirstRow("SELECT * FROM $table WHERE alert_status=1 AND level='witel' AND witel_id=%i", $witelId);
            return $group ?? null;
        });
    }

    public static function findAlertRegionalGroup($regionalId)
    {
        return TelegramUser::query(function ($db, $table) use ($regionalId) {
            $group = $db->queryFirstRow("SELECT * FROM $table WHERE alert_status=1 AND level='regional' AND regional_id=%i", $regionalId);
            return $group ?? null;
        });
    }

    public static function findAlertNasionalGroup()
    {
        return TelegramUser::query(function ($db, $table) {
            $group = $db->queryFirstRow("SELECT * FROM $table WHERE alert_status=1 AND level='nasional'");
            return $group ?? null;
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