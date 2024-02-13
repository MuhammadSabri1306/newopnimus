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
            $query = "SELECT * FROM $table WHERE id IN %li ORDER BY location_sname";
            return $db->query($query, $locIds);
        });
    }

    public static function create(array $data)
    {
        $data['timestamp'] = date('Y-m-d H:i:s');
        return RtuLocation::query(function ($db, $table) use ($data) {
            $db->insert($table, $data);
            $id = $db->insertId();
            return $id ? RtuLocation::find($id) : null;
        });
    }

    public static function update($id, array $data)
    {
        return RtuLocation::query(function ($db, $table) use ($id, $data) {
            return $db->update($table, $data, "id=%i", $id);
        });
    }
}