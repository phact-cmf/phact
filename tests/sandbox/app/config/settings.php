<?php

return [
    'name' => 'New phact application',
    'paths' => [
        'base' => __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
    ],
    'modules' => [
        'Test'
    ],
    'servicesConfig' => __DIR__ . DIRECTORY_SEPARATOR . "services.yml",
    'autoloadComponents' => [
        'errorHandler',
        'translate'
    ]
];