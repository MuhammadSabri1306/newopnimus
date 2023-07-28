<?php

// Load composer
require_once __DIR__ . '/../vendor/autoload.php';

// Load all configuration options
/** @var array $config */
$config = require __DIR__ . '/../config.php';

// Load App
require __DIR__.'/autoload.php';

function useHelper($helperName) {
    require __DIR__."/Helper/$helperName.php";
}

useHelper('main');