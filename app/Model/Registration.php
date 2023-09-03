<?php
namespace App\Model;

use App\Core\Model;
use App\Model\TelegramAdmin;

class Registration extends Model
{
    public static $table = 'registration';

    public static function create(array $data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        if(isset($data['data'])) {
            $data['data'] = json_encode($data['data']);
        }

        return Registration::query(function ($db, $table) use ($data) {
            $db->insert($table, $data);
            $id = $db->insertId();
            return $id ? Registration::find($id) : null;
        });
    }

    public static function find($id)
    {
        return Registration::query(function ($db, $table) use ($id) {
            $data = $db->queryFirstRow("SELECT * FROM $table WHERE id=%i", $id);
            if(!$data) return null;

            $data['data'] = json_decode($data['data'], true);
            return $data;
        });
    }

    public static function findByChatId($chatId)
    {
        return Registration::query(function ($db, $table) use ($chatId) {
            $data = $db->queryFirstRow("SELECT * FROM $table WHERE chat_id=%i", $chatId);
            if(!$data) return null;

            $data['data'] = json_decode($data['data'], true);
            return $data;
        });
    }

    public static function findUnprocessedByChatId($chatId)
    {
        return Registration::query(function ($db, $table) use ($chatId) {
            $data = $db->queryFirstRow("SELECT * FROM $table WHERE status=%s AND chat_id=%i", 'unprocessed', $chatId);
            if(!$data) return null;

            $data['data'] = json_decode($data['data'], true);
            return $data;
        });
    }

    public static function getStatus($id)
    {
        $data = Registration::find($id);
        if(!$data || $data['status'] == 'unprocessed') {
            return $data;
        }

        $adminId = $data['updated_by'];
        $admin = TelegramAdmin::query(function ($db, $table) use ($adminId) {
            $regionalTable = Regional::$table;
            $witelTable = Witel::$table;
            $query = "SELECT $table.*, $regionalTable.name AS regional_name, $witelTable.witel_name FROM $table ".
                "LEFT JOIN $regionalTable ON $regionalTable.id=$table.regional_id ".
                "LEFT JOIN $witelTable ON $witelTable.id=$table.witel_id ".
                "WHERE $table.id=%i";
            $data = $db->queryFirstRow($query, $adminId);
            return $data ?? null;
        });

        $data['updated_by'] = $admin;
        return $data;
    }

    public static function update($id, array $data, $adminId)
    {
        $data['updated_by'] = $adminId;
        $data['updated_at'] = date('Y-m-d H:i:s');
        if(isset($data['data'])) {
            $data['data'] = json_encode($data['data']);
        }
        
        return Registration::query(function ($db, $table) use ($id, $data) {
            return $db->update($table, $data, "id=%i", $id);
        });
    }

    public static function delete($id)
    {
        return Registration::query(function ($db, $table) use ($id) {
            return $db->delete($table, 'id=%i', $id);
        });
    }
}