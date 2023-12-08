<?php
namespace App\Model;

use App\Core\Model;
use App\Core\Model\Traits\QueryPatternTraits;
use App\Core\Model\QueryPattern\QueryPattern;

class AlarmPortStatus extends Model
{
    use QueryPatternTraits;

    public static $table = 'alarm_port_status';

    protected static function getBasicPattern()
    {
        $pattern = new QueryPattern(AlarmPortStatus::$table);
        $pattern->addCollumn('id');
        $pattern->addCollumn('port_no');
        $pattern->addCollumn('port_name');
        $pattern->addCollumn('port_value');
        $pattern->addCollumn('port_unit');
        $pattern->addCollumn('port_severity');
        $pattern->addCollumn('type');
        $pattern->addCollumn('rtu_sname');
        $pattern->addCollumn('rtu_status');
        $pattern->addCollumn('is_closed');
        $pattern->addCollumn('opened_at');
        $pattern->addCollumn('closed_at');
        return $pattern;
    }


    public static function useDefaultJoinPattern()
    {
        static::$activeQueryPattern = 'default_join';
    }

    protected static function getDefaultJoinPattern($tableAlias = [])
    {
        $pattern = new QueryPattern(AlarmPortStatus::$table, 'alarm');

        $pattern->table->rtu = \App\Model\RtuList::$table;
        $pattern->table->loc = \App\Model\RtuLocation::$table;
        $pattern->table->datel = \App\Model\Datel::$table;
        $pattern->table->witel = \App\Model\Witel::$table;
        $pattern->table->treg = \App\Model\Regional::$table;

        $pattern->addTableJoin('rtu.sname', 'alarm.rtu_sname', 'LEFT JOIN');
        $pattern->addTableJoin('loc.id', 'rtu.location_id', 'LEFT JOIN');
        $pattern->addTableJoin('datel.id', 'rtu.datel_id', 'LEFT JOIN');
        $pattern->addTableJoin('witel.id', 'rtu.witel_id', 'LEFT JOIN');
        $pattern->addTableJoin('treg.id', 'rtu.regional_id', 'LEFT JOIN');

        $pattern->addCollumn('id', 'alarm.id');
        $pattern->addCollumn('port_no', 'alarm.port_no');
        $pattern->addCollumn('port_name', 'alarm.port_name');
        $pattern->addCollumn('port_value', 'alarm.port_value');
        $pattern->addCollumn('port_unit', 'alarm.port_unit');
        $pattern->addCollumn('port_severity', 'alarm.port_severity');
        $pattern->addCollumn('type', 'alarm.type');
        $pattern->addCollumn('rtu_sname', 'alarm.rtu_sname');
        $pattern->addCollumn('rtu_status', 'alarm.rtu_status');
        $pattern->addCollumn('is_closed', 'alarm.is_closed');
        $pattern->addCollumn('opened_at', 'alarm.opened_at');
        $pattern->addCollumn('closed_at', 'alarm.closed_at');
        $pattern->addCollumn('rtu_name', 'rtu.name');
        $pattern->addCollumn('location_id', 'rtu.location_id');
        $pattern->addCollumn('datel_id', 'rtu.datel_id');
        $pattern->addCollumn('witel_id', 'rtu.witel_id');
        $pattern->addCollumn('regional_id', 'rtu.regional_id');
        $pattern->addCollumn('location_name', 'loc.location_name');
        $pattern->addCollumn('location_sname', 'loc.location_sname');
        $pattern->addCollumn('datel_name', 'datel.datel_name');
        $pattern->addCollumn('witel_name', 'witel.witel_name');
        $pattern->addCollumn('witel_code', 'witel.witel_code');

        $pattern->addCollumn('regional_name', 'treg.name');
        $pattern->addCollumn('regional_code', 'treg.divre_code');

        $joinQuery = $pattern->joinsQuery;
        $pattern->setTableQueryGetter(function($table) use ($joinQuery) {
            return "$table->name AS $table->alias $joinQuery";
        });

        return $pattern;
    }

    public static function getAll()
    {
        $pattern = static::getQueryPattern();
        $query = "SELECT $pattern->collumnsQuery FROM $pattern->tableQuery";
        return AlarmPortStatus::query(fn($db) => $db->query($query) ?? []);
    }

    public static function getRegionalCurrDay($regionalId)
    {
        $dateTime = new \DateTime();
        $dateTime->setTime(0, 0, 0);
        $dateTimeStr = $dateTime->format('Y-m-d H:i:s');

        $pattern = static::getQueryPattern();
        $colls = $pattern->collumns;

        $query = "SELECT $pattern->collumnsQuery FROM $pattern->tableQuery".
            " WHERE $colls->regional_id=%i_regionalId AND $colls->opened_at>=%s_openedAt".
            " ORDER BY $colls->rtu_sname, $colls->port_name";
        $params = [ 'regionalId' => $regionalId, 'openedAt' => $dateTimeStr ];

        return AlarmPortStatus::query(fn($db) => $db->query($query, $params) ?? []);
    }

    public static function getWitelCurrDay($witelId)
    {
        $dateTime = new \DateTime();
        $dateTime->setTime(0, 0, 0);
        $dateTimeStr = $dateTime->format('Y-m-d H:i:s');

        $pattern = static::getQueryPattern();
        $colls = $pattern->collumns;

        $query = "SELECT $pattern->collumnsQuery FROM $pattern->tableQuery WHERE $colls->witel_id=%i_witelId AND $colls->opened_at>=%s_openedAt";
        $params = [ 'witelId' => $witelId, 'openedAt' => $dateTimeStr ];

        return AlarmPortStatus::query(fn($db) => $db->query($query, $params) ?? []);
    }

    public static function getRegionalCurrMonth($regionalId)
    {
        $dateTime = new \DateTime();
        $dateTime->modify('first day of this month');
        $dateTime->setTime(0, 0, 0);
        $dateTimeStr = $dateTime->format('Y-m-d H:i:s');

        $pattern = static::getQueryPattern();
        $colls = $pattern->collumns;
        
        $query = "SELECT $pattern->collumnsQuery FROM $pattern->tableQuery WHERE $colls->regional_id=%i_regionalId AND $colls->opened_at>=%s_openedAt";
        $params = [ 'regionalId' => $regionalId, 'openedAt' => $dateTimeStr ];

        return AlarmPortStatus::query(fn($db) => $db->query($query, $params) ?? []);
    }

    public static function getWitelCurrMonth($witelId)
    {
        $dateTime = new \DateTime();
        $dateTime->modify('first day of this month');
        $dateTime->setTime(0, 0, 0);
        $dateTimeStr = $dateTime->format('Y-m-d H:i:s');

        $pattern = static::getQueryPattern();
        $colls = $pattern->collumns;
        
        $query = "SELECT $pattern->collumnsQuery FROM $pattern->tableQuery WHERE $colls->witel_id=%i_witelId AND $colls->opened_at>=%s_openedAt";
        $params = [ 'witelId' => $witelId, 'openedAt' => $dateTimeStr ];

        return AlarmPortStatus::query(fn($db) => $db->query($query, $params) ?? []);
    }

    public static function find($id)
    {
        $pattern = static::getQueryPattern();
        $colls = $pattern->collumns;
        $query = "SELECT $pattern->collumnsQuery FROM $pattern->tableQuery WHERE $colls->id=%i";

        return AlarmPortStatus::query(fn($db) => $db->queryFirstRow($query, $id) ?? null);
    }
}