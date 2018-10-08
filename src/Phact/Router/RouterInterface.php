<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 23/09/2018 10:29
 */

namespace Phact\Router;


use Exception;

interface RouterInterface
{
    /**
     * Set current handling route name
     * @param $name
     */
    public function setCurrentName($name);

    /**
     * Get current handling route name
     * @return string
     */
    public function getCurrentName();

    /**
     * Get current handling route namespace
     * @return string
     */
    public function getCurrentNamespace();

    /**
     * Retrieves all routes.
     * Useful if you want to process or display routes.
     * @return array All routes.
     */
    public function getRoutes();

    /**
     * Add multiple routes at once from array in the following format:
     *
     *   $routes = array(
     *      array($method, $route, $target, $name)
     *   );
     *
     * @param array $routes
     * @return void
     * @author Koen Punt
     * @throws Exception
     */
    public function addRoutes($routes);

    /**
     * Set the base path.
     * Useful if you are running your application from a subdirectory.
     * @param $basePath
     */
    public function setBasePath($basePath);

    /**
     * Add named match types. It uses array_merge so keys can be overwritten.
     *
     * @param array $matchTypes The key is the name and the value is the regex.
     */
    public function addMatchTypes($matchTypes);

    /**
     * Map a route to a target
     *
     * @param string $method One of 5 HTTP Methods, or a pipe-separated list of multiple HTTP Methods (GET|POST|PATCH|PUT|DELETE)
     * @param string $route The route regex, custom regex must start with an @. You can use multiple pre-set regex filters, like [i:id]
     * @param mixed $target The target where this route should point to. Can be anything.
     * @param string $name Optional name of this route. Supply if you want to reverse route this url in your application.
     * @throws Exception
     */
    public function map($method, $route, $target, $name = null);

    /**
     * Reversed routing
     *
     * Generate the URL for a named route. Replace regexes with supplied parameters
     *
     * @param string $routeName The name of the route.
     * @param array @params Associative array of parameters to replace placeholders with.
     * @return string The URL of the route with named parameters in place.
     * @throws Exception
     */
    public function url($routeName, $params = []);

    /**
     * Match a given Request Url against stored routes
     * @param string $requestUrl
     * @param string $requestMethod
     * @return array|boolean Array with route information on success, false on failure (no match).
     * @throws Exception
     */
    public function match($requestUrl = null, $requestMethod = null);

    /**
     * Append routes from file
     *
     * @param $path
     * @throws Exception
     */
    public function collectFromFile($path);

    /**
     * Append routes from array
     *
     * @param array $configuration
     * @param string $namespace
     * @param string $route
     * @throws Exception
     */
    public function collect($configuration = [], $namespace = '', $route = '');


    /**
     * Append routes
     *
     * @param $item
     * @param string $namespace
     * @param string $route
     * @throws Exception
     */
    public function appendRoutes($item, $namespace = '', $route = '');

    /**
     * Append single route
     * @param $item
     * @param string $namespace
     * @param string $route
     * @throws Exception
     */
    public function appendRoute($item, $namespace = '', $route = '/');
}