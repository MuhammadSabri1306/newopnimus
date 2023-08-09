<?php
namespace App\Model;

use App\Core\Model;

class RtuLocation extends Model
{
    protected static $table = 'rtu_location';
    
    public static function getAll()
    {
        return RtuLocation::query(function ($db, $table) use ($userId) {
            return $db->query("SELECT * FROM $table");
        });
    }
    
    public static function find($id)
    {
        return RtuLocation::query(function ($db, $table) use ($id) {
            return $db->queryFirstRow("SELECT * FROM $table WHERE id=%i", $id);
        });
    }
}