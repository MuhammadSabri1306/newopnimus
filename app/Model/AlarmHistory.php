<?php
namespace App\Model;

use App\Core\Model;
use App\Model\Regional;
use App\Model\Witel;
use App\Model\RtuLocation;
use App\Model\RtuList;
use App\Helper\ArrayHelper;
use App\Helper\DateHelper;

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

    public static function getStat($options = [])
    {
        $dateTime = new \DateTime();
        if(isset($options['startDate'])) {
            if(!$options['startDate'] instanceof \DateTime) {
                throw new \Exception('options startDate should be instance of \DateTime');
            }
            $dateTime = $options['startDate'];
        }

        $filters = [];
        $filters['openedAt'] = [
            'query' => 'alarm.opened_at>=%s_{{key}}',
            'value' => $dateTime->format('Y-m-d H:i:s')
        ];

        if(isset($options['witelId'])) {
            $filters['witelId'] = [
                'query' => 'rtu.witel_id=%i_{{key}}',
                'value' => $options['witelId']
            ];
        } elseif(isset($options['regionalId'])) {
            $filters['regionalId'] = [
                'query' => 'rtu.regional_id=%i_{{key}}',
                'value' => $options['regionalId']
            ];
        }

        $alarms = static::query(function($db, $table) use ($filters) {
            $tableTreg = Regional::$table;
            $tableWtl = Witel::$table;
            $tableLoc = RtuLocation::$table;
            $tableRtu = RtuList::$table;

            $params = [];
            $where = [];
            foreach($filters as $key => $filter) {
                $params[$key] = $filter['value'];
                $filterQuery = str_replace('{{key}}', $key, $filter['query']);
                array_push($where, $filterQuery);
            }

            $select = [
                'alarm.*', 'rtu.name AS rtu_name',
                'rtu.location_uuid', 'loc.location_name', 'loc.location_sname',
                'rtu.witel_id', 'wtl.witel_name', 'wtl.witel_code',
                'rtu.regional_id', 'treg.name AS regional_name', 'treg.divre_code AS regional_code'
            ];

            $querySelect = implode(', ', $select);
            $queryWhere = implode(' AND ', $where);
            $query = "SELECT $querySelect FROM $table AS alarm LEFT JOIN $tableRtu AS rtu ON rtu.sname=alarm.rtu_sname".
                " LEFT JOIN $tableLoc AS loc ON loc.uuid=rtu.location_uuid LEFT JOIN $tableWtl AS wtl ON wtl.id=rtu.witel_id".
                " LEFT JOIN $tableTreg AS treg ON treg.id=rtu.regional_id WHERE $queryWhere".
                ' ORDER BY treg.name, wtl.witel_name, loc.location_sname, alarm.opened_at';

            return $db->query($query, $params) ?? [];
        });

        $rtuStates = [];
        $portsAlarm = [];
        foreach($alarms as $alarm) {

            $alarmOpenDate = $alarm['opened_at'];
            if($alarm['alert_start_time']) {
                $alarmOpenDate = date('Y-m-d H:i:s', $alarm['alert_start_time'] / 1000);
            }

            $isRtuDown = !in_array(strtolower($alarm['rtu_status']), [ 'normal', 'alert' ]);
            if($isRtuDown) {
                $rsIndex = ArrayHelper::findIndex($rtuStates, fn($item) => $item['rtu_sname'] == $alarm['rtu_sname']);
                if($rsIndex < 0) {
                    $rtuRegexp = '/^(?!rtu_status$)(rtu_|location_|datel_|witel_|regional_)/';
                    $rtu = ArrayHelper::duplicateByKeysRegex($alarm, $rtuRegexp);
                    $rsIndex = count($rtuStates);
                    array_push($rtuStates, [
                        ...$rtu,
                        'down_count' => 0,
                        'last_rtu_status' => null,
                        'last_down_at' => null
                    ]);
                }

                $downDate = $alarmOpenDate;
                if($rtuStates[$rsIndex]['last_down_at']) {
                    $downDate = DateHelper::max($rtuStates[$rsIndex]['last_down_at'], $alarmOpenDate);
                }
                $rtuStates[$rsIndex]['last_down_at'] = $downDate;

                if($rtuStates[$rsIndex]['last_rtu_status'] != $alarm['rtu_status']) {
                    $rtuStates[$rsIndex]['last_rtu_status'] = $alarm['rtu_status'];
                    $rtuStates[$rsIndex]['down_count']++;
                }
            }

            $paIndex = ArrayHelper::findIndex($portsAlarm, fn($item) => $item['port_id'] == $alarm['port_id']);
            if($paIndex < 0) {
                $portRegexp = '/^(?!port_value$|port_severity$|rtu_status$)(port_|rtu_|location_|datel_|witel_|regional_)/';
                $port = ArrayHelper::duplicateByKeysRegex($alarm, $portRegexp);
                $paIndex = count($portsAlarm);
                array_push($portsAlarm, [
                    ...$port,
                    'count' => 0,
                    'is_open' => false,
                    'last_port_severity' => null,
                    'last_port_value' => null,
                    'last_opened_at' => null,
                ]);
            }

            $openDate = $alarmOpenDate;
            if($portsAlarm[$paIndex]['last_opened_at']) {
                $openDate = DateHelper::max($portsAlarm[$paIndex]['last_opened_at'], $alarmOpenDate);
            }
            $portsAlarm[$paIndex]['count']++;
            $portsAlarm[$paIndex]['is_open'] = $alarm['closed_at'] ? false : true;
            $portsAlarm[$paIndex]['last_port_severity'] = $alarm['port_severity'];
            $portsAlarm[$paIndex]['last_port_value'] = $alarm['port_value'];
            $portsAlarm[$paIndex]['last_opened_at'] = $openDate;

        }

        $totalRtu = count($rtuStates);
        $totalPort = 0;
        $totalOpenedPort = 0;
        $totalClosedPort = 0;
        foreach($portsAlarm as $port) {
            $totalPort++;
            if($port['is_open']) {
                $totalOpenedPort++;
            } else {
                $totalClosedPort++;
            }
        }

        return [
            'rtu_states' => $rtuStates,
            'ports_alarm' => $portsAlarm,
            'total_rtu' => $totalRtu,
            'total_port' => $totalPort,
            'total_opened_port' => $totalOpenedPort,
            'total_closed_port' => $totalClosedPort,
        ];
    }
}