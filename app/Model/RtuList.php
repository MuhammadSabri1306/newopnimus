<?php
namespace App\Model;

use App\Core\Model;

class RtuList extends Model
{
    public static $table = 'rtu_list';
    
    public static function getAll()
    {
        return RtuList::query(function ($db, $table) use ($userId) {
            return $db->query("SELECT * FROM $table");
        });
    }
    
    public static function find($id)
    {
        return RtuList::query(function ($db, $table) use ($id) {
            return $db->queryFirstRow("SELECT * FROM $table WHERE id=%i", $id);
        });
    }
    
    public static function findBySname($sname)
    {
        return RtuList::query(function ($db, $table) use ($sname) {
            return $db->queryFirstRow("SELECT * FROM $table WHERE sname=%s", $sname);
        });
    }

    public static function getSnameOrderedByLocation($locationId)
    {
        return RtuList::query(function ($db, $table) use ($locationId) {
            return $db->query("SELECT * FROM $table WHERE location_id=%i ORDER BY sname", $locationId);
        });
    }
}