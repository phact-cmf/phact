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

use Phact\Helpers\Paths;

class Routes
{
    public $basePath;
    public $path;
    public $namespace;

    public function __construct($basePath, $path, $namespace = null)
    {
        $this->basePath = $basePath;
        $this->path = $path;
        $this->namespace = $namespace;
    }

    public function getRoutes()
    {
        $result = [];
        $routesPath = Paths::file($this->path, 'php');
        if (!$routesPath) {
            return $result;
        }
        $routes = include $routesPath;
        foreach ($routes as $route) {
            if ($route instanceof Route) {
                $route->namespace = $this->namespace;
                $route->basePath = $this->basePath;
                $result[] = $route;
            } elseif ($route instanceof Routes) {
                $route->namespace = $this->namespace . ':' . $route->namespace;
                $route->basePath = $this->basePath . $route->basePath;
                $result = array_merge($result, $route->getRoutes());
            }
        }
        return $routes;
    }
}