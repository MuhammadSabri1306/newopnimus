<?php
namespace App\Model;

use App\Core\Model;

class Witel extends Model
{
    public static $table = 'witel';
    
    public static function getAll()
    {
        $witels = Witel::query(function ($db, $table) {
            return $db->query("SELECT * FROM $table");
        });

        return $witels;
    }

    public static function getNameOrdered($regionalId = null)
    {
        $witels = Witel::query(function ($db, $table) use ($regionalId) {
            if(!$regionalId) {
                return $db->query("SELECT * FROM $table ORDER BY witel_name");
            } else {
                return $db->query("SELECT * FROM $table WHERE regional_id=%i ORDER BY witel_name", $regionalId);
            }
        });

        return $witels;
    }
    
    public static function find($id)
    {
        $witel = Witel::query(function ($db, $table) use ($id) {
            return $db->queryFirstRow("SELECT * FROM $table WHERE id=%i", $id);
        });

        return $witel;
    }
    
    public static function create(array $data)
    {
        $data['timestamp'] = date('Y-m-d H:i:s');
        return Witel::query(function ($db, $table) use ($data) {
            $db->insert($table, $data);
            $id = $db->insertId();
            return $id ? Witel::find($id) : null;
        });
    }

    public static function update($id, array $data)
    {
        return Witel::query(function ($db, $table) use ($id, $data) {
            return $db->update($table, $data, "id=%i", $id);
        });
    }
}