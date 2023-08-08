<?php
namespace App\Model;

use App\Core\Model;

class TelegramAdmin extends Model
{
    protected static $table = 'telegram_admin';

    public static function find($id)
    {
        return TelegramAdmin::query(function ($db, $table) use ($id) {
            $data = $db->queryFirstRow("SELECT * FROM $table WHERE id=%i", $id);
            return $data ?? null;
        });
    }

    public static function getByUserArea(array $user, string $levelKey = 'level', string $regionalKey = 'regional_id', string $witelKey = 'witel_id')
    {
        $admins = TelegramAdmin::query(function ($db, $table) use ($user, $levelKey, $regionalKey, $witelKey) {
            if($user[$levelKey] == 'nasional' || $user[$levelKey] == 'regional') {
                return $db->query("SELECT * FROM $table WHERE level=%s", 'nasional');
            }

            $regionalId = isset($user[$regionalKey]) ? $user[$regionalKey] : null;
            if($user[$levelKey] == 'witel') {
                $query = "SELECT * FROM $table WHERE level=%s_level AND regional_id=%i_regional_id";
                return $db->query($query, [
                    'level' => 'regional',
                    'regional_id' => $regionalId
                ]);
            }

            $witelId = isset($user[$witelKey]) ? $user[$witelKey] : null;
            if($user[$levelKey] == 'pic') {
                $query = "SELECT * FROM $table WHERE level=%s_level AND witel_id=%i_witel_id";
                return $db->query($query, [
                    'level' => 'witel',
                    'witel_id' => $witelId
                ]);
            }
            
            return [];
        });

        return $admins;
    }
}