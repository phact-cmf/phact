<?php

return [
    'name' => 'New phact application',
    'modules' => [
        'Test'
    ],
    'components' => [
        'path' => [
            'class' => \Phact\Components\Path::class,
            'properties' => [
                'paths' => [
                    'base' => __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
                ]
            ]
        ],
        'errorHandler' => [
            'class' => \Phact\Main\ErrorHandler::class
        ],
        'translate' => [
            'class' => \Phact\Translate\Translate::class
        ],
        'cliRequest' => [
            'class' => \Phact\Request\CliRequest::class
        ],
        'request' => [
            'class' => \Phact\Request\HttpRequest::class
        ],
        'router' => [
            'class' => \Phact\Router\Router::class,
            'arguments' => [
                'configPath' => 'base.config.routes'
            ]
        ],
        'events' => [
            'class' => \Phact\Event\EventManager::class
        ],
        'template' => [
            'class' => \Phact\Template\TemplateManager::class
        ]
    ],
    'autoloadComponents' => [
        'errorHandler',
        'translate'
    ]
];