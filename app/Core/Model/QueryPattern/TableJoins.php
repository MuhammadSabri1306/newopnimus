<?php
namespace App\Core\Model\QueryPattern;

use App\Core\Model\QueryPattern\TableJoin;

class TableJoins
{
    private $joins = [];

    public function set(TableJoin $tableJoin)
    {
        $joinsCount = count($this->joins);
        $key = $tableJoin->targetTableName;

        for($i=0; $i<$joinsCount; $i++) {
            if($key == $this->joins[$i]->targetTableName) {
                $this->joins[$i] = $tableJoin;
                $i = $joinsCount;
                return;
            }
        }
        array_push($this->joins, $tableJoin);
    }

    public function __get(string $key)
    {
        $joinsCount = count($this->joins);
        for($i=0; $i<$joinsCount; $i++) {
            if($key == $this->joins[$i]->targetTableName) {
                $tableJoin = $this->joins[$i];
                $i = $joinsCount;
                return $tableJoin;
            }
        }
        return null;
    }

    public function getQuery()
    {
        $joinQueries = array_map(fn($join) => $join->getQuery(), $this->joins);
        return implode(' ', $joinQueries);
    }
}