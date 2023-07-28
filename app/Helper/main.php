<?php

function dd(...$vars) {
    echo '<style>';
    echo 'pre { background-color: #f6f8fa; padding: 10px; }';
    echo 'strong { color: #e91e63; }';
    echo '</style>';

    foreach ($vars as $var) {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
    }
    die();
}