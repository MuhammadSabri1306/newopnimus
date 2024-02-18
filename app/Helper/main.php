<?php

function useHelper($helperName) {
    require_once __DIR__."/$helperName.php";
}

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

function dd_json($var, ...$vars) {
    header('Content-Type: application/json; charset=utf-8');
    if(count($vars) < 1) {
        echo json_encode($var, JSON_INVALID_UTF8_IGNORE);
        die();
    }

    echo json_encode([$var, ...$vars], JSON_INVALID_UTF8_IGNORE);
    die();
}

function toErrorText($err) {
    $errMessage = $err->getMessage();
    $errFile = $err->getFile();
    $errLine = $err->getLine();
    $text = "$errMessage\n  at $errFile:$errLine";

    foreach($err->getTrace() as $errTrace) {
        if(isset($errTrace['file'], $errTrace['line'])) {
            $errFile = $errTrace['file'];
            $errLine = $errTrace['line'];
            $text .= "\n  at $errFile:$errLine";
        }
    }

    return $text;
}

function debugError($err, $exit = true) {

    echo '<style>';
    echo 'pre { background-color: #f6f8fa; padding: 10px; }';
    echo 'strong { color: #e91e63; }';
    echo '</style>';

    ?><pre><?=toErrorText($err)?></pre><?php

    if($exit) {
        die();
    }

}

function readErrorStack($err) {
    $errStack = [];
    while($err) {

        $text = $err->getMessage() . ' in ' . $err->getFile() . ':' . $err->getLine();
        array_push($errStack, $text);
        $err = $err->getPrevious();

    }
    return $errStack;
}