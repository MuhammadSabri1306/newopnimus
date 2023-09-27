<?php

function getConfig() {
    return json_decode(json_encode([

        'userTesting' => [
            'chatId' => 1931357638
        ],

        'newosase_auth' => [
            'application' => 'Opnimus',
            'token' => 'p5cUT_y5EzIWS4kcedLWAPwWyilVJMg3R6GEhGnUnUjFZKeeTO',
            'db_table' => 'newosase_api_token'
        ]

    ]));
}