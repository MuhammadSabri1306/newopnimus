<?php

spl_autoload_register(function ($className) {
    // Define the base directory for your project
    $baseDir = __DIR__;

    // Map the namespace prefix to the corresponding base directory
    $namespaceMap = [
        'App\\Core\\' => "$baseDir/Core/",
        'App\\Controller\\' => "$baseDir/Controller/",
        'App\\Model\\' => "$baseDir/Model/",
        'App\\BuiltMessageText\\' => "$baseDir/BuiltMessageText/",
        'App\\ApiRequest\\' => "$baseDir/ApiRequest/",
        'App\\Request\\' => "$baseDir/Request/",
        'MuhammadSabri1306\\MyBotLogger\\' => "$baseDir/../my-bot-logger/php/",
        'MuhammadSabri1306\\PhpChartSvg\\' => "$baseDir/../MuhammadSabri1306/php-chart-svg/",
    ];

    // Exclude the Helper namespace
    if (strpos($className, 'App\\Helper\\') === 0) {
        return;
    }

    // Loop through the namespace map and check if the class belongs to it
    foreach ($namespaceMap as $namespacePrefix => $baseDirectory) {
        if (strpos($className, $namespacePrefix) === 0) {
            $relativeClass = substr($className, strlen($namespacePrefix));
            $classFile = $baseDirectory . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($classFile)) {
                require_once $classFile;
            }
            break;
        }
    }
});