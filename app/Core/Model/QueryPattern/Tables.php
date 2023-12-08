<?php
namespace App\Core\Model\QueryPattern;

use App\Core\Model\QueryPattern\Table;

class Tables
{
    private $tablesList = [];

    public function __construct(string $defaultTableName, string $defaultTableAlias = null)
    {
        $this->add($defaultTableName, $defaultTableAlias);
    }

    public function __set(string $tableAlias, string $tableName)
    {
        $countTables = count($this->tablesList);
        for($i=0; $i<$countTables; $i++) {

            if($tableAlias == 'default' || $tableAlias == $this->tablesList[$i]->alias) {
                $this->tablesList[$i] = new Table();
                $this->tablesList[$i]->name = $tableName;
                $this->tablesList[$i]->alias = $tableAlias ?? $tableName;
                $i = $countTables;
                return;
            }

        }
        
        $this->add($tableName, $tableAlias);
    }

    public function add(string $tableName, string $tableAlias = null)
    {
        $table = new Table();
        $table->name = $tableName;
        $table->alias = $tableAlias ?? $tableName;
        array_push($this->tablesList, $table);
    }

    public function __get(string $propName)
    {
        if(property_exists(Table::class, $propName)) {
            return $this->tablesList[0]->$propName;
        }

        $tableAlias = $propName;
        return $this->find($tableAlias);
    }

    public function find(string $tableAlias)
    {
        $countTables = count($this->tablesList);
        for($i=0; $i<$countTables; $i++) {

            if($tableAlias == 'default' || $tableAlias == $this->tablesList[$i]->alias) {
                $table = $this->tablesList[$i];
                $i = $countTables;
                return $table;
            }

        }
        return null;
    }

    public function isMultiple()
    {
        return count($this->tablesList) > 1;
    }
}