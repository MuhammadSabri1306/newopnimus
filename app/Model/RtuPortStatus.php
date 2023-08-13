<?php
namespace App\Model;

use App\Core\Model;

class RtuPortStatus extends Model
{
    public static $table = 'rtu_port_status';
    
    public static function getAll()
    {
        return RtuPortStatus::query(function ($db, $table) {
            return $db->query("SELECT * FROM $table");
        });
    }
    
    public static function find($id)
    {
        return RtuPortStatus::query(function ($db, $table) use ($id) {
            return $db->queryFirstRow("SELECT * FROM $table WHERE id=%i", $id);
        });
    }

    public static function getExistsAlarm(array $params)
    {
        return RtuPortStatus::query(function ($db, $table) use ($params) {
            $rtuTable = RtuList::$table;
            $locTable = 'rtu_location';
            $datelTable = 'datel';
            $witelTable = Witel::$table;
            $regTable = Regional::$table;

            $fieldsQuery = implode(', ', [
                "$table.*",
                "$rtuTable.name AS rtu_name",
                "$rtuTable.id AS rtu_id",
                "$rtuTable.location_id",
                "$rtuTable.datel_id",
                "$rtuTable.witel_id",
                "$rtuTable.regional_id",
                "$locTable.location_name",
                "$locTable.location_sname AS location_code",
                "$datelTable.datel_name",
                "$witelTable.witel_name",
                "$witelTable.witel_code",
                "$regTable.name AS regional_name",
                "$regTable.divre_code AS regional_code",
            ]);

            $joinsQuery = implode(' ', [
                "LEFT JOIN $rtuTable ON $rtuTable.sname=$table.rtu_code",
                "LEFT JOIN $locTable ON $locTable.id=$rtuTable.location_id",
                "LEFT JOIN $datelTable ON $datelTable.id=$rtuTable.datel_id",
                "LEFT JOIN $witelTable ON $witelTable.id=$rtuTable.witel_id",
                "LEFT JOIN $regTable ON $regTable.id=$rtuTable.regional_id",
            ]);

            $query = "SELECT $fieldsQuery FROM $table $joinsQuery WHERE $table.state=%i_state";
            $bindData = [ 'state' => 1 ];
            
            if(isset($params['locations'])) {
                $query .= " AND $locTable.id IN %li_loc_id";
                $bindData['loc_id'] = $params['locations'];
                return $db->query($query, $bindData);
            }

            if(isset($params['witel'])) {
                $query .= " AND $witelTable.id=%i_witel_id";
                $bindData['witel_id'] = $params['witel'];
                return $db->query($query, $bindData);
            }

            if(isset($params['regional'])) {
                $query .= " AND $regTable.id=%i_regional_id";
                $bindData['regional_id'] = $params['regional'];
                return $db->query($query, $bindData);
            }

        });
    }
}