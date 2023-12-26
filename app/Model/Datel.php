<?php
namespace App\Model;

use App\Core\Model;

class Datel extends Model
{
    public static $table = 'datel';

    public static function getAll()
    {
        return Datel::query(function ($db, $table) use ($userId) {
            return $db->query("SELECT * FROM $table");
        });
    }

    public static function create(array $data)
    {
        $data['timestamp'] = date('Y-m-d H:i:s');
        return Datel::query(function ($db, $table) use ($data) {
            $db->insert($table, $data);
            $id = $db->insertId();
            return $id ? Datel::find($id) : null;
        });
    }

    public static function update($id, array $data)
    {
        return Datel::query(function ($db, $table) use ($id, $data) {
            return $db->update($table, $data, "id=%i", $id);
        });
    }
}