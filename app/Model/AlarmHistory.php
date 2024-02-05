<?php
namespace App\Model;

use App\Core\Model;
use App\Model\RtuList;

class AlarmHistory extends Model
{
    public static $table = 'alarm_history';

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

    public static function getCurrDayByWitelDesc($witelId)
    {
        $dateTime = new \DateTime();
        $dateTime->setTime(0, 0, 0);
        $dateTimeStr = $dateTime->format('Y-m-d H:i:s');

        return static::query(function($db, $table) use ($witelId, $dateTimeStr) {
            $tableRtu = RtuList::$table;
            $query = "SELECT alarm.* FROM $table AS alarm JOIN $tableRtu AS rtu ON rtu.sname=alarm.rtu_sname".
                ' WHERE rtu.witel_id=%i_witelId AND alarm.opened_at>=%s_openedAt ORDER BY alarm.opened_at DESC';
            $params = [ 'witelId' => $witelId, 'openedAt' => $dateTimeStr ];
            return $db->query($query, $params) ?? [];
        });
    }

    public static function getCurrDayByRtuDesc($rtuSname)
    {
        $dateTime = new \DateTime();
        $dateTime->setTime(0, 0, 0);
        $dateTimeStr = $dateTime->format('Y-m-d H:i:s');

        return static::query(function($db, $table) use ($rtuSname, $dateTimeStr) {
            $query = "SELECT * FROM $table AS alarm WHERE rtu_sname=%s_rtuSname AND opened_at>=%s_openedAt ORDER BY opened_at DESC";
            $params = [ 'rtuSname' => $rtuSname, 'openedAt' => $dateTimeStr ];
            return $db->query($query, $params) ?? [];
        });
    }
}