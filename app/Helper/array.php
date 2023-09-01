<?php

function findArray(array $data, callable $checker) {
    foreach($data as $item) {
        if($checker($item)) {
            return $item;
        }
    }
    return null;
}

function findArrayIndex(array $data, callable $checker) {
    for($index=0; $index<count($data); $index++) {
        if($checker($data[$index])) {
            return $index;
        }
    }
    return -1;
}