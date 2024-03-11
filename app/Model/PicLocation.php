<?php
namespace App\Model;

use App\Core\Model;
use App\Model\RtuLocation;

class PicLocation extends Model
{
    public static $table = 'pic_location';

    public static function create(array $data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');

        return PicLocation::query(function ($db, $table) use ($data) {
            $db->insert($table, $data);
            $id = $db->insertId();
            return $id ? PicLocation::find($id) : null;
        });
    }

    public static function getByUser($userId)
    {
        return PicLocation::query(function ($db, $table) use ($userId) {
            $locationTable = RtuLocation::$table;
            $query = "SELECT $table.*, $locationTable.location_name, $locationTable.location_sname FROM $table ".
                "LEFT JOIN $locationTable ON $locationTable.id=$table.location_id WHERE user_id=%i";
            return $db->query($query, $userId);
        });
    }

    public static function getByUsers($userIds)
    {
        return PicLocation::query(function ($db, $table) use ($userIds) {
            $locationTable = RtuLocation::$table;
            $query = "SELECT $table.*, $locationTable.location_name, $locationTable.location_sname FROM $table ".
                "LEFT JOIN $locationTable ON $locationTable.id=$table.location_id WHERE user_id IN %li";
            return $db->query($query, $userIds);
        });
    }

    public static function find($id)
    {
        return PicLocation::query(function ($db, $table) use ($id) {
            return $db->queryFirstRow("SELECT * FROM $table WHERE id=%i", $id) ?? null;
        });
    }

    public static function update($id, array $data)
    {
        return PicLocation::query(function ($db, $table) use ($id, $data) {
            return $db->update($table, $data, "id=%i", $id);
        });
    }

    public static function delete($id)
    {
        return PicLocation::query(function ($db, $table) use ($id) {
            return $db->delete($table, 'id=%i', $id);
        });
    }

    public static function deleteByUserId($userId)
    {
        return PicLocation::query(function ($db, $table) use ($userId) {
            return $db->delete($table, 'user_id=%i', $userId);
        });
    }
}