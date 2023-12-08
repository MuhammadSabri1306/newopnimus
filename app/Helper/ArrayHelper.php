<?php
namespace App\Helper;

class ArrayHelper
{
    public static function find(array $data, callable $checker) {
        foreach($data as $item) {
            if($checker($item)) return $item;
        }
        return null;
    }
    
    public static function findIndex(array $data, callable $checker) {
        for($index=0; $index<count($data); $index++) {
            if($checker($data[$index])) return $index;
        }
        return -1;
    }

    public static function inArrayCollumn($searchValue, array $data, string $collumnKey)
    {
        $collumns = array_column($data, $collumnKey);
        return in_array($searchValue, $collumns);
    }

    public static function duplicateByKeys(array $data, array $keys)
    {
        $result = [];
        foreach($keys as $key) {
            $result[$key] = $data[$key];
        }
        return $result;
    }

    public static function duplicateByKeysRegex(array $data, string $regexKey)
    {
        $result = [];
        foreach($data as $key => $value) {
            if(preg_match($regexKey, $key)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    public static function sort(array $data, callable $compare)
    {
        usort($data, function ($a, $b) use ($compare) {
            list($lowerOrderVal, $higherOrderVal) = $compare($a, $b);
            if ($lowerOrderVal == $higherOrderVal) return 0;
            return ($lowerOrderVal < $higherOrderVal) ? -1 : 1;
        });
        return $data;
    }

    public static function sortStr(array $data, callable $compare)
    {
        usort($data, function($a, $b) use ($compare) {
            list($lowerOrderVal, $higherOrderVal) = $compare($a, $b);
            return strcmp($lowerOrderVal, $higherOrderVal);
        });
        return $data;
    }

    public static function sortByKey(array $data, string $order = 'asc')
    {
        $order = strtolower($order);
        if($order == 'desc') {
            krsort($data);
        } else {
            ksort($data);
        }
        return $data;
    }
}