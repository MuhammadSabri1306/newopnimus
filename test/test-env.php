<?php
require __DIR__.'/../app/bootstrap.php';
// use App\Config\BotConfig;
// use App\Config\AppConfig;

try {

    dd_json(\App\Config\BotConfig::build(), $config);

} catch(\Throwable $err) {
    echo $err;
}