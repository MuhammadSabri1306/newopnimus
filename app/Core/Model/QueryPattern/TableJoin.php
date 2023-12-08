<?php
namespace App\Core\Model\QueryPattern;

use App\Core\Model\QueryPattern\Table;

class TableJoin
{
    public $joinQuery;

    public $srcTableName;
    public $srcTableAlias;
    public $srcField;

    public $targetTableName;
    public $targetTableAlias;
    public $targetField;

    public function __construct(string $joinQuery)
    {
        $this->joinQuery = $joinQuery;
    }

    public function setSrcTable(Table $table, string $key)
    {
        $this->srcTableName = $table->name;
        $this->srcTableAlias = $table->alias;
        $this->srcField = $key;
    }

    public function setTargetTable(Table $table, string $key)
    {
        $this->targetTableName = $table->name;
        $this->targetTableAlias = $table->alias;
        $this->targetField = $key;
    }

    public function getQuery()
    {
        $targetField = "$this->targetTableAlias.$this->targetField";
        $srcField = "$this->srcTableAlias.$this->srcField";

        return "$this->joinQuery $this->targetTableName AS $this->targetTableAlias ON $targetField=$srcField";
    }
}