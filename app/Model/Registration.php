<?php
namespace App\Model;

use App\Core\Model;

class Registration extends Model
{
    protected static $table = 'registration';

    public static function create(array $data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');

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
            return $data ?? null;
        });
    }

    public static function getStatus($id)
    {
        return Registration::query(function ($db, $table) use ($id) {
            $data = $db->queryFirstRow("SELECT * FROM $table WHERE id=%i", $id);
            if(!$data) return null;
            
            if($data['status'] == 'unprocessed') {
                return [ 'status' => $data['status'] ];
            }

            $adminId = $data['updated_by'];
            $admin = TelegramAdmin::query(function ($db, $table) use ($adminId) {
                $regionalTable = Regional::$table;
                $witelTable = Witel::$table;
                $query = "SELECT $table.*, $regionalTable.name AS regional_name, $witelTable.witel_name FROM $table ".
                    "LEFT JOIN $regionalTable ON $regionalTable.id=$table.regional_id ".
                    "LEFT JOIN $witelTable ON $witelTable.id=$table.witel_id ".
                    'WHERE id=%i';
                $data = $db->queryFirstRow($query, $adminId);
                return $data ?? null;
            });

            return [
                'status' => $data['status'],
                'updated_by' => $admin
            ];
        });
    }

    public static function update($id, $data)
    {
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