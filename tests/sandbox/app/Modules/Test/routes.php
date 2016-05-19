<?php

use Phact\Router\Route;

return [
    new Route('/test_route', [\Modules\Test\Controllers\TestController::class, 'test'], 'test')
];