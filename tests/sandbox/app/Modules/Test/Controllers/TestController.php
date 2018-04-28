<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 19/05/16 07:48
 */

namespace Modules\Test\Controllers;

use Phact\Controller\Controller;

class TestController extends Controller
{
    public function test()
    {
        echo 'test';
    }

    public function testParam($name)
    {
        echo 'Name: ' . $name;
    }
}