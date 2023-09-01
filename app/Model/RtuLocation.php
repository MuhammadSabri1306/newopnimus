<?php
namespace App\Model;

use App\Core\Model;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\Datel;

class RtuLocation extends Model
{
    public static $table = 'rtu_location';
    
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

    public static function getSnameOrderedByWitel($witelId)
    {
        return RtuLocation::query(function ($db, $table) use ($witelId) {
            $datelTable = Datel::$table;
            $query = "SELECT $table.* FROM $table JOIN $datelTable ON $datelTable.id=$table.datel_id ".
                "WHERE $datelTable.witel_id=%i ORDER BY location_sname";

            return $db->query($query, $witelId);
        });
    }

    public static function getByIds(array $locIds)
    {
        return RtuLocation::query(function ($db, $table) use ($locIds) {
            $query = "SELECT * FROM $table WHERE id IN %li ORDER BY FIELD%ll";
            return $db->query($query, $locIds, ['id', ...$locIds]);
        });
    }
}