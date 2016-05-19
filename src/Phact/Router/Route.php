<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company HashStudio
 * @site http://hashstudio.ru
 * @date 19/05/16 07:44
 */

namespace Phact\Router;

class Route
{
    public $route;
    public $target;
    public $methods;
    public $name;

    public $basePath;
    public $namespace;

    public function __construct($route, $target, $name = null, $methods = ['GET', 'POST'])
    {
        $this->route = $route;
        $this->target = $target;
        $this->methods = $methods;
        $this->name = $name;
    }

    public function getFullRoute()
    {
        return $this->basePath . $this->route;
    }

    public function getFullName()
    {
        $namespace = $this->namespace ? $this->namespace . ':' : '';
        return $namespace . $this->name;
    }
}