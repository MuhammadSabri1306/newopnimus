<?php

function convertToNumber($value) {
    if($value === null || strtolower($value) === 'null') {
        return 0;
    }
    
    if(is_numeric($value) && strpos($value, '.') !== false) {
        return (double)$value;
    }
    
    if(is_numeric($value) && strpos($value, '.') === false) {
        return (int)$value;
    }

    return null;
}
