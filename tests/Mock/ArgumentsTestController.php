<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 23/09/2018 11:26
 */

namespace Phact\Tests\Mock;


use Phact\Controller\Controller;
use Phact\Router\RouterInterface;

class ArgumentsTestController extends Controller
{
    public function arguments($name, $value)
    {
        echo $name . ' - ' . $value;
    }

    public function di($name, RouterInterface $router)
    {
        echo $name . ' - ' . get_class($router);
    }

    public function diReversed(RouterInterface $router, $name)
    {
        echo $name . ' - ' . get_class($router);
    }
}