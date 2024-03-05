<?php
require __DIR__.'/../app/bootstrap.php';

try {
    $cmd = 'pgrep -a node';
    $output = shell_exec($cmd);
    dd_json(compact('cmd', 'output'));
} catch(\Throwable $err) {
    debugError($err);
}