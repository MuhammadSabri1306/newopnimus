<?php
namespace App\Core\Model\QueryPattern;

class Collumns
{
    private $collumns = [];
    private $useTableAlias = false;

    public static function setItem(Collumns $collumn, Tables $table, string $key, $field, $tableAlias)
    {
        if($tableAlias && !$field) return;

        if($table->isMultiple()) {

            if(!$tableAlias) {
                $tableAlias = $table->alias;
            }

            if(!$collumn->useTableAlias) {
                foreach($collumn->collumns as $key => $item) {
                    if(!$item['tableAlias']) {
                        $collumn->collumns[$key]['tableAlias'] = $tableAlias;
                    }
                }
                $collumn->useTableAlias = true;
            }

        } elseif($collumn->useTableAlias) {

            foreach($collumn->collumns as $key => $item) {
                $collumn->collumns[$key]['tableAlias'] = null;
            }

            $collumn->useTableAlias = false;

        }

        $collumn->collumns[$key] = [
            'field' => $field ?? $key,
            'tableAlias' => $tableAlias
        ];

        return $collumn;
    }

    public function __get(string $key)
    {
        return $this->get($key);
    }

    public function get(string $key = null)
    {
        if(!$key) {
            $collumns = [];
            foreach($this->collumns as $key => $collumn) {
                $collumns[$key] = $collumn['tableAlias'] ? $collumn['tableAlias'].'.'.$collumn['field'] : $collumn['field'];
            }
            return $collumns;
        }

        if(!array_key_exists($key, $this->collumns)) {
            return null;
        }

        $collumn = $this->collumns[$key];
        return $collumn['tableAlias'] ? $collumn['tableAlias'].'.'.$collumn['field'] : $collumn['field'];
    }

    public function getQuery()
    {
        $fields = [];
        foreach($this->collumns as $key => $collumn) {

            $colField = $collumn['field'];
            if($collumn['tableAlias']) $colField = $collumn['tableAlias'] . '.' . $colField;

            if($collumn['field'] != $key) $colField .= ' AS ' . $key;
            array_push($fields, $colField);

        }
        return implode(', ', $fields);
    }
}