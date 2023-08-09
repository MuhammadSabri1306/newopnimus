<?php
namespace App\Model;

use App\Core\Model;

class PicLocation extends Model
{
    protected static $table = 'pic_location';
    
    public static function getByUser($userId)
    {
        return PicLocation::query(function ($db, $table) use ($userId) {
            $locationTable = RtuLocation::$table;
            $query = "SELECT $table.*, $locationTable.location_name, $locationTable.location_sname, FROM $table ".
                "LEFT JOIN $locationTable ON $locationTable.id=$table.location_id WHERE user_id=%i";
            return $db->query($query, $userId);
        });
    }
    
    public static function find($id)
    {
        return PicLocation::query(function ($db, $table) use ($id) {
            return $db->queryFirstRow("SELECT * FROM $table WHERE id=%i", $id);
        });
    }
}