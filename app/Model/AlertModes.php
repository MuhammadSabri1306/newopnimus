<?php
namespace App\Model;

use App\Core\Model;

class AlertModes extends Model
{
    public static $table = 'alert_modes';

    public static function getAll()
    {
        return static::query(function($db, $table) {
            return $db->query("SELECT * FROM $table") ?? [];
        });
    }

    public static function find($id)
    {
        return static::query(function($db, $table) use ($id) {
            return $db->queryFirstRow("SELECT * FROM $table WHERE id=%i", $id) ?? null;
        });
    }
}