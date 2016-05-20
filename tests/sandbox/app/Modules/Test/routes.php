<?php

return [
    [
        'route' => '/test_route',
        'target' => [\Modules\Test\Controllers\TestController::class, 'test'],
        'name' => 'test'
    ]
];