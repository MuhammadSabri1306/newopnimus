<?php
session_start();

require __DIR__.'/../app/bootstrap.php';

use App\Core\SessionDebugger;

// SessionDebugger::clear();
$recordedData = SessionDebugger::getAll();
dd($recordedData);