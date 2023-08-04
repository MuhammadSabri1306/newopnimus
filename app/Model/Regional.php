<?php
namespace App\Model;

use App\Core\Model;

class Regional extends Model
{
    protected static $table = 'regional';
    
    public static function getAll()
    {
        $regionals = Regional::query(function ($db, $table) {
            return $db->query("SELECT * FROM $table");
        });

        return $regionals;
    }
    
    public static function getSnameOrdered()
    {
        $regionals = Regional::query(function ($db, $table) {
            return $db->query("SELECT * FROM $table ORDER BY sname");
        });

        return $regionals;
    }
    
    public static function find($id)
    {
        $regional = Regional::query(function ($db, $table) use ($id) {
            return $db->queryFirstRow("SELECT * FROM $table WHERE id=%i", $id);
        });

        return $regional;
    }
}