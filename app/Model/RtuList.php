<?php
namespace App\Model;

use App\Core\Model;

class RtuList extends Model
{
    public static $table = 'rtu_list';

    public static function isUUID($id)
    {
        return is_string($id) && preg_match('/\D/', $id);
    }
    
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

    public static function findByUUID($uuid)
    {
        return RtuList::query(function ($db, $table) use ($uuid) {
            return $db->queryFirstRow("SELECT * FROM $table WHERE uuid=%i", $uuid);
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

    public static function create(array $data)
    {
        $data['timestamp'] = date('Y-m-d H:i:s');
        return RtuList::query(function ($db, $table) use ($data) {
            $db->insert($table, $data);
            $id = $db->insertId();
            return $id ? RtuList::find($id) : null;
        });
    }

    public static function update($id, array $data)
    {
        return RtuList::query(function ($db, $table) use ($id, $data) {
            return $db->update($table, $data, "id=%i", $id);
        });
    }
}