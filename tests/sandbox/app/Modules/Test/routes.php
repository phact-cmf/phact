<?php

return [
    [
        'route' => '/test_route',
        'target' => [\Modules\Test\Controllers\TestController::class, 'test'],
        'name' => 'test'
    ],
    [
        'route' => '/test_route/{:name}',
        'target' => [\Modules\Test\Controllers\TestController::class, 'testParam'],
        'name' => 'test_param'
    ]
];