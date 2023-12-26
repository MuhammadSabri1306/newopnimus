<?php
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__.'/../..');
$dotenv->load();
    
$dotenv->required('APP_MODE')->notEmpty()->allowedValues(['production', 'development']);

$dotenv->required('PUBLIC_URL')->notEmpty();
$dotenv->required('DEV_PUBLIC_URL');

$dotenv->required('BOT_TOKEN')->notEmpty();
$dotenv->required('BOT_USERNAME')->notEmpty();
$dotenv->required('BOT_HOOK_URL')->notEmpty();
$dotenv->required('BOT_PRIVATE_KEY')->notEmpty();

$dotenv->required('MYSQL_DEFAULT_HOST')->notEmpty();
$dotenv->ifPresent('MYSQL_DEFAULT_PORT')->isInteger();
$dotenv->required('MYSQL_DEFAULT_USERNAME')->notEmpty();
$dotenv->required('MYSQL_DEFAULT_PASSWORD')->notEmpty();
$dotenv->required('MYSQL_DEFAULT_DATABASE')->notEmpty();

$dotenv->required('MYSQL_BOT_HOST')->notEmpty();
$dotenv->ifPresent('MYSQL_BOT_PORT')->isInteger();
$dotenv->required('MYSQL_BOT_USERNAME')->notEmpty();
$dotenv->required('MYSQL_BOT_PASSWORD')->notEmpty();
$dotenv->required('MYSQL_BOT_DATABASE')->notEmpty();
$dotenv->required('MYSQL_BOT_PREFIX');

$dotenv->required('DEV_TEST_CHAT_ID')->notEmpty();
$dotenv->required('DEV_LOG_CHAT_ID')->notEmpty();

$dotenv->required('OSASEAPI_APP_ID')->notEmpty();
$dotenv->required('OSASEAPI_TOKEN')->notEmpty();
$dotenv->required('OSASEAPI_MYSQL_TABLE')->notEmpty();