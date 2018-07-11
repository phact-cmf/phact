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
        'errorHandler' => [
            'class' => \Phact\Main\ErrorHandler::class
        ],
        'translate' => [
            'class' => \Phact\Translate\Translate::class
        ],
        'request' => [
            'class' => \Phact\Request\RequestManager::class,
            'httpRequest' => [
                'class' => \Phact\Request\HttpRequest::class,
                'session' => [
                    'class' => \Phact\Request\Session::class
                ]
            ],
            'cliRequest' => [
                'class' => \Phact\Request\CliRequest::class,
            ]
        ],
        'router' => [
            'class' => \Phact\Router\Router::class,
            'pathRoutes' => 'base.config.routes'
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