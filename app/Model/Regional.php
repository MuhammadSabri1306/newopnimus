<?php
namespace App\Model;

use App\Core\Model;

class Regional extends Model
{
    public static $table = 'regional';
    
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

    public static function create(array $data)
    {
        $data['timestamp'] = date('Y-m-d H:i:s');
        return Regional::query(function ($db, $table) use ($data) {
            $db->insert($table, $data);
            $id = $db->insertId();
            return $id ? Regional::find($id) : null;
        });
    }

    public static function update($id, array $data)
    {
        return Regional::query(function ($db, $table) use ($id, $data) {
            return $db->update($table, $data, "id=%i", $id);
        });
    }
}