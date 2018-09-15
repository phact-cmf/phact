<?php

return [
    'default' => [
        'host' => '127.0.0.1',
        'dbname' => 'phact',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8',
        'driver' => 'pdo_mysql',
    ],
    'pgsql' => [
        'host' => '127.0.0.1',
        'dbname' => 'phact',
        'user' => 'postgres',
        'password' => '',
        'charset' => 'utf8',
        'driver' => 'pdo_pgsql',
    ],
    'sqlite' => [
        'memory' => true,
        'driver' => 'pdo_sqlite',
        'driverOptions' => [
            'userDefinedFunctions' => [
                'REGEXP' => [
                    'callback' => ['Phact\Orm\Adapters\SqliteAdapter', 'udfRegexp'],
                    'numArgs' => -1
                ]
            ]
        ]
    ]
];