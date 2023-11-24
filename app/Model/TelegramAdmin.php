<?php
namespace App\Model;

use App\Core\Model;

class TelegramAdmin extends Model
{
    public static $table = 'telegram_admin';

    public static function find($id)
    {
        return TelegramAdmin::query(function ($db, $table) use ($id) {
            $data = $db->queryFirstRow("SELECT * FROM $table WHERE id=%i", $id);
            return $data ?? null;
        });
    }

    public static function findByChatId($chatId)
    {
        return TelegramAdmin::query(function ($db, $table) use ($chatId) {
            $user = $db->queryFirstRow("SELECT * FROM $table WHERE chat_id=%s", $chatId);
            return $user ?? null;
        });
    }

    public static function getByUserArea(array $user, string $levelKey = 'level', string $regionalKey = 'regional_id', string $witelKey = 'witel_id')
    {
        return TelegramAdmin::query(function ($db, $table) use ($user, $levelKey, $regionalKey, $witelKey) {
            if($user[$levelKey] == 'nasional') {
                return $db->query("SELECT * FROM $table WHERE level=%s", 'nasional');
            }

            $regionalId = isset($user[$regionalKey]) ? $user[$regionalKey] : null;
            if($user[$levelKey] == 'regional') {
                $query = "SELECT * FROM $table WHERE (level=%s_level_nas) OR (level=%s_level_reg AND regional_id=%i_reg_id) ".
                    'ORDER BY regional_id DESC';
                $params = [
                    'level_nas' => 'nasional',
                    'level_reg' => 'regional',
                    'reg_id' => $regionalId,
                ];

                $admins = $db->query($query, $params);
                // if(count($admins) < 1) return [];
                // if($admins[0]['level'] == 'nasional') return $admins;
                // return array_filter($admins, fn($item) => $item['level'] == 'regional');
                return $admins;
            }

            $witelId = isset($user[$witelKey]) ? $user[$witelKey] : null;
            if($user[$levelKey] == 'witel' || $user[$levelKey] == 'pic') {
                $query = "SELECT * FROM $table WHERE (level=%s_level_nas) OR (level=%s_level_reg AND regional_id=%i_reg_id) ".
                    'OR (level=%s_level_wit AND witel_id=%i_wit_id) ORDER BY witel_id DESC, regional_id DESC';
                $params = [
                    'level_nas' => 'nasional',
                    'level_reg' => 'regional',
                    'level_wit' => 'witel',
                    'reg_id' => $regionalId,
                    'wit_id' => $witelId,
                ];

                $admins = $db->query($query, $params);
                // if(count($admins) < 1) return [];
                // if($admins[0]['level'] == 'nasional') return $admins;
                // if($admins[0]['level'] == 'regional') return array_filter($admins, fn($item) => $item['level'] == 'regional');
                // return array_filter($admins, fn($item) => $item['level'] == 'witel');
                return $admins;
            }
            
            return [];
        });
    }

    public static function getSuperAdmin()
    {
        return TelegramAdmin::query(function ($db, $table) {
            $admins = $db->query("SELECT * FROM $table WHERE is_super_admin=1");
            return $admins ?? [];
        });
    }
}