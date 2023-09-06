<?php
namespace App\Model;

use App\Core\Model;

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
    
    public static function find($id)
    {
        return PicLocation::query(function ($db, $table) use ($id) {
            return $db->queryFirstRow("SELECT * FROM $table WHERE id=%i", $id) ?? null;
        });
    }
}