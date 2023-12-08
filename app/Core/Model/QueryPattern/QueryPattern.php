<?php
namespace App\Core\Model\QueryPattern;

use App\Core\Model\QueryPattern\Tables;
use App\Core\Model\QueryPattern\Collumns;
use App\Core\Model\QueryPattern\TableJoin;
use App\Core\Model\QueryPattern\TableJoins;

class QueryPattern
{
    public Tables $table;
    public Collumns $collumns;

    private TableJoins $tableJoins;

    private $tableQueryGetter;

    public function __construct(string $defaultTableName, string $defaultTableAlias = null)
    {
        $this->table = new Tables($defaultTableName, $defaultTableAlias);
        $this->collumns = new Collumns();
        $this->tableJoins = new TableJoins();
        $this->setTableQueryGetter();
    }

    public function addCollumn(string $key, string $field = null)
    {
        if(!$field) $field = $key;

        $fieldArr = explode('.', $field);
        $fieldName = end($fieldArr);
        $tableAlias = count($fieldArr) > 1 ? $fieldArr[0] : null;

        $this->collumns = Collumns::setItem($this->collumns, $this->table, $key, $fieldName, $tableAlias);
    }

    public function addTableJoin(string $targetQuery, string $srcQuery, string $joinQuery = 'JOIN')
    {
        $tableJoin = new TableJoin($joinQuery);

        list($targetTableAlias, $targetField) = explode('.', $targetQuery);
        $targetTable = $this->table->find($targetTableAlias);
        $tableJoin->setTargetTable($targetTable, $targetField);

        list($srcTableAlias, $srcField) = explode('.', $srcQuery);
        $srcTable = $this->table->find($srcTableAlias);
        $tableJoin->setSrcTable($srcTable, $srcField);

        $this->tableJoins->set($tableJoin);
    }

    public function setTableQueryGetter(callable $getter = null)
    {
        if($getter) {
            $this->tableQueryGetter = $getter;
        } else {
            $this->tableQueryGetter = function(Tables $table) {
                return $table->default->name;
            };
        }
    }

    public function __get($key)
    {
        if($key == 'collumnsQuery') {
            return $this->collumns->getQuery();
        }

        if($key == 'tableQuery') {
            $tableQueryGetter = $this->tableQueryGetter;
            return $tableQueryGetter($this->table);
        }

        if($key == 'joinsQuery') {
            return $this->tableJoins->getQuery();
        }

        return null;
    }
}