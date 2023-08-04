<?php
namespace App\Model;

use App\Core\Model;

class Witel extends Model
{
    protected static $table = 'witel';
    
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
}