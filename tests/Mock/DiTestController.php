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
use Phact\Request\HttpRequestInterface;
use Phact\Router\RouterInterface;

class DiTestController extends Controller
{
    protected $_router;

    public function __construct(RouterInterface $router, HttpRequestInterface $request)
    {
        $this->_router = $router;
        parent::__construct($request);
    }

    public function test()
    {
        echo get_class($this->_router);
    }
}