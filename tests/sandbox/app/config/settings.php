<?php

return [
    'name' => 'New phact application',
    'paths' => [
        'base' => implode(DIRECTORY_SEPARATOR, [__DIR__, '..'])
    ],
    'modules' => [
        'Test'
    ],
    'components' => [
        'request' => [
            'class' => \Phact\Request\Request::class
        ],
        'router' => [
            'class' => \Phact\Router\Router::class,
            'pathRoutes' => 'base.config.routes'
        ],
        'events' => [
            'class' => \Phact\Event\EventManager::class
        ]
    ]
];