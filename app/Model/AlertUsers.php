<?php
namespace App\Model;

use App\Core\Model;
use App\Core\Model\Traits\QueryPatternTraits;
use App\Core\Model\QueryPattern\QueryPattern;

class AlertUsers extends Model
{
    use QueryPatternTraits;

    public static $table = 'alert_users';

    protected static function getBasicPattern()
    {
        $pattern = new QueryPattern(static::$table);
        $pattern->addCollumn('alert_user_id', 'id');
        $pattern->addCollumn('id', 'telegram_user_id');
        $pattern->addCollumn('mode_id');
        $pattern->addCollumn('cron_alert_status');
        $pattern->addCollumn('user_alert_status');
        $pattern->addCollumn('is_pivot_group');
        $pattern->addCollumn('pivot_level');
        $pattern->addCollumn('pivot_id');
        $pattern->addCollumn('created_at');
        $pattern->addCollumn('updated_at');
        return $pattern;
    }

    public static function useDefaultJoinPattern()
    {
        static::$activeQueryPattern = 'default_join';
    }

    protected static function getDefaultJoinPattern($tableAlias = [])
    {
        $pattern = new QueryPattern(static::$table, 'config');

        $pattern->table->user = \App\Model\TelegramUser::$table;
        $pattern->table->mode = 'alert_modes';
        $pattern->table->witel = \App\Model\Witel::$table;
        $pattern->table->treg = \App\Model\Regional::$table;

        $pattern->addTableJoin('user.id', 'config.telegram_user_id', 'JOIN');
        $pattern->addTableJoin('mode.id', 'config.mode_id', 'LEFT JOIN');
        $pattern->addTableJoin('witel.id', 'user.witel_id', 'LEFT JOIN');
        $pattern->addTableJoin('treg.id', 'user.regional_id', 'LEFT JOIN');

        $pattern->addCollumn('alert_user_id', 'config.id');
        $pattern->addCollumn('id', 'config.telegram_user_id');
        $pattern->addCollumn('mode_id', 'config.mode_id');
        $pattern->addCollumn('cron_alert_status', 'config.cron_alert_status');
        $pattern->addCollumn('user_alert_status', 'config.user_alert_status');
        $pattern->addCollumn('is_pivot_group', 'config.is_pivot_group');
        $pattern->addCollumn('pivot_level', 'config.pivot_level');
        $pattern->addCollumn('pivot_id', 'config.pivot_id');
        $pattern->addCollumn('created_at', 'config.created_at');
        $pattern->addCollumn('updated_at', 'config.updated_at');

        $pattern->addCollumn('chat_id', 'user.chat_id');
        $pattern->addCollumn('username', 'user.username');
        $pattern->addCollumn('username', 'user.username');
        $pattern->addCollumn('first_name', 'user.first_name');
        $pattern->addCollumn('last_name', 'user.last_name');
        $pattern->addCollumn('type', 'user.type');
        $pattern->addCollumn('is_pic', 'user.is_pic');
        $pattern->addCollumn('group_description', 'user.group_description');
        $pattern->addCollumn('level', 'user.level');
        $pattern->addCollumn('witel_id', 'user.witel_id');
        $pattern->addCollumn('regional_id', 'user.regional_id');

        $joinQuery = $pattern->joinsQuery;
        $pattern->setTableQueryGetter(function($table) use ($joinQuery) {
            return "$table->name AS $table->alias $joinQuery";
        });

        return $pattern;
    }

    public static function useFullJoinPattern()
    {
        static::$activeQueryPattern = 'full_join';
    }

    protected static function getFullJoinPattern($tableAlias = [])
    {
        $pattern = new QueryPattern(static::$table, 'config');

        $pattern->table->user = \App\Model\TelegramUser::$table;
        $pattern->table->mode = \App\Model\AlertModes::$table;
        $pattern->table->witel = \App\Model\Witel::$table;
        $pattern->table->treg = \App\Model\Regional::$table;

        $pattern->addTableJoin('user.id', 'config.telegram_user_id', 'JOIN');
        $pattern->addTableJoin('mode.id', 'config.mode_id', 'LEFT JOIN');
        $pattern->addTableJoin('witel.id', 'user.witel_id', 'LEFT JOIN');
        $pattern->addTableJoin('treg.id', 'user.regional_id', 'LEFT JOIN');

        $pattern->addCollumn('alert_user_id', 'config.id');
        $pattern->addCollumn('id', 'config.telegram_user_id');
        $pattern->addCollumn('mode_id', 'config.mode_id');
        $pattern->addCollumn('cron_alert_status', 'config.cron_alert_status');
        $pattern->addCollumn('user_alert_status', 'config.user_alert_status');
        $pattern->addCollumn('is_pivot_group', 'config.is_pivot_group');
        $pattern->addCollumn('pivot_level', 'config.pivot_level');
        $pattern->addCollumn('pivot_id', 'config.pivot_id');
        $pattern->addCollumn('created_at', 'config.created_at');
        $pattern->addCollumn('updated_at', 'config.updated_at');

        $pattern->addCollumn('chat_id', 'user.chat_id');
        $pattern->addCollumn('username', 'user.username');
        $pattern->addCollumn('username', 'user.username');
        $pattern->addCollumn('first_name', 'user.first_name');
        $pattern->addCollumn('last_name', 'user.last_name');
        $pattern->addCollumn('type', 'user.type');
        $pattern->addCollumn('is_pic', 'user.is_pic');
        $pattern->addCollumn('group_description', 'user.group_description');
        $pattern->addCollumn('level', 'user.level');
        $pattern->addCollumn('witel_id', 'user.witel_id');
        $pattern->addCollumn('regional_id', 'user.regional_id');

        $pattern->addCollumn('mode_name', 'mode.name');
        $pattern->addCollumn('mode_rules', 'mode.rules');
        $pattern->addCollumn('mode_rules_file', 'mode.rules_file');
        $pattern->addCollumn('apply_mode_rules_file', 'mode.apply_rules_file');

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
        return static::query(fn($db) => $db->query($query) ?? []);
    }

    public static function find($id)
    {
        $pattern = static::getQueryPattern();
        $colls = $pattern->collumns;

        $query = "SELECT $pattern->collumnsQuery FROM $pattern->tableQuery WHERE $colls->id=%i";
        return static::query(fn($db) => $db->queryFirstRow($query, $id) ?? null);
    }

    public static function findByChatId($chatId)
    {
        $pattern = static::getQueryPattern();
        $colls = $pattern->collumns;

        $query = "SELECT $pattern->collumnsQuery FROM $pattern->tableQuery WHERE $colls->chat_id=%s";
        return static::query(fn($db) => $db->queryFirstRow($query, $chatId) ?? null);
    }

    public static function chatIdExists($chatId)
    {
        return static::findByChatId($chatId) ? true : false; 
    }

    public static function update($id, array $params)
    {
        if(static::$activeQueryPattern != 'basic') {
            static::useBasicPattern();
        }

        $pattern = static::getQueryPattern();
        $collId = $pattern->collumns->alert_user_id;
        $collUpdatedAt = $pattern->collumns->updated_at;

        $data = [];
        foreach($params as $key => $val) {
            $field = $pattern->collumns->get($key);
            $data[$field] = $val;
        }
        $data[$collUpdatedAt] = date('Y-m-d H:i:s');

        return static::query(function ($db, $table) use ($id, $data, $collId) {
            return $db->update($table, $data, "$collId=%i", $id);
        });
    }

    public static function create(array $params)
    {
        if(static::$activeQueryPattern != 'basic') {
            static::useBasicPattern();
        }

        $pattern = static::getQueryPattern();
        $collCreatedAt = $pattern->collumns->created_at;
        $data = [];
        foreach($params as $key => $val) {
            $field = $pattern->collumns->get($key);
            $data[$field] = $val;
        }
        $data[$collCreatedAt] = date('Y-m-d H:i:s');

        return static::query(function ($db, $table) use ($data) {
            $db->insert($table, $data);
            $id = $db->insertId();
            return $id ? AlertUsers::find($id) : null;
        });
    }

    public static function findPivot($level, $pivotId = null)
    {
        $pattern = static::getQueryPattern();
        $colls = $pattern->collumns;

        $query = "SELECT $pattern->collumnsQuery FROM $pattern->tableQuery WHERE $colls->pivot_level=%s_plevel";
        $params = [ 'plevel' => $level ];
        if($pivotId) {
            $query .= " AND $colls->pivot_id=%i_pid";
            $params['pid'] = $pivotId;
        }

        return static::query(fn($db) => $db->queryFirstRow($query, $params) ?? null);
    }
}