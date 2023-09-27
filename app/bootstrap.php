<?php

// Load composer
require_once __DIR__ . '/../vendor/autoload.php';

// Load all configuration options
require __DIR__ . '/config.php';
$appConfig = getConfig();

/** @var array $config */
$config = require __DIR__ . '/../config.php';

// Load App
require __DIR__.'/autoload.php';
require __DIR__.'/Helper/main.php';

date_default_timezone_set('Asia/Jakarta');