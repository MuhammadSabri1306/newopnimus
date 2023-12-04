<?php

require_once __DIR__.'/../app/bootstrap.php';

try {

    $configNew = \App\Config\BotConfig::buildArray();

    ?><style>
        main {
            display: flex;
            gap: 1rem;
        }
        pre { background-color: #f6f8fa; padding: 10px; }
        strong { color: #e91e63; }
    </style>
    <main>
        <pre><?php var_dump($config); ?></pre>
        <pre><?php var_dump($configNew); ?></pre>
    </main><?php

} catch(\Throwable $err) {
    echo $err;
}