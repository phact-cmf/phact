<?php

return [
    'name' => 'New phact application',
    'modules' => [
        'Test'
    ],
    'components' => [
        'db' => [
            'class' => \Phact\Orm\ConnectionManager::class
        ],
        'form_manager' => [
            'class' => \Phact\Form\Configuration\ConfigurationManager::class,
        ],
        'form' => [
            'class' => \Phact\Form\Configuration\ConfigurationProvider::class,
            'constructMethod' => 'getInstance',
            'calls' => [
                'setManager' => ['@form_manager']
            ]
        ],
        'orm_manager' => [
            'class' => \Phact\Orm\Configuration\ConfigurationManager::class,
        ],
        'orm' => [
            'class' => \Phact\Orm\Configuration\ConfigurationProvider::class,
            'constructMethod' => 'getInstance',
            'calls' => [
                'setManager' => ['@orm_manager']
            ]
        ],
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
        'translate',
        'form',
        'orm'
    ]
];