<?php

namespace Phact\Router;

use Exception;
use InvalidArgumentException;
use Phact\Cache\CacheDriverInterface;
use Phact\Components\PathInterface;
use Phact\Event\EventManagerInterface;
use Phact\Event\Events;
use Phact\Exceptions\DependencyException;
use Phact\Helpers\SmartProperties;
use Phact\Helpers\Text;
use Phact\Main\Phact;
use Traversable;

class Router implements RouterInterface
{
    use SmartProperties, Events;

    public $fixTrailingSlash = true;

    /**
     * @var array Default HTTP-methods
     */
    public $defaultMethods = ['GET', 'POST'];

    /**
     * @var array Array of all routes (incl. named routes).
     */
    protected $_routes = array();

    /**
     * @var array Array of all named routes.
     */
    protected $_namedRoutes = array();

    /**
     * @var string Can be used to ignore leading part of the Request URL (if main file lives in subdirectory of host)
     */
    protected $_basePath = '';

    /**
     * @var int Cache timeout
     */
    public $cacheTimeout;

    /**
     * @var string
     */
    protected $_currentName;

    protected $_matched = [];

    /**
     * @var array Array of default match types (regex helpers)
     */
    protected $_matchTypes = array(
        'i' => '[0-9]++',
        'a' => '[0-9A-Za-z]++',
        's' => '[0-9A-Za-z\-]++',
        'slug' => '[0-9A-Za-z_\-]++',
        'h' => '[0-9A-Fa-f]++',
        '*' => '.+?',
        '**' => '.++',
        '' => '[^/\.]++'
    );

    /**
     * @var CacheDriverInterface
     */
    protected $_cacheDriver;

    /**
     * @var PathInterface
     */
    protected $_path;

    public function __construct(string $configPath = null, PathInterface $path = null, CacheDriverInterface $cacheDriver = null, EventManagerInterface $eventManager = null)
    {
        $this->_cacheDriver = $cacheDriver;
        $this->_eventManager = $eventManager;
        $this->_path = $path;

        $routes = null;
        $cacheKey = 'PHACT__ROUTER';
        if (!is_null($this->cacheTimeout)) {
            $routes = $this->_cacheDriver->get($cacheKey);
            if ($routes) {
                $this->_namedRoutes = $routes['named'];
                $this->_routes = $routes['all'];
            }
            $this->_matched = $this->getMatchedRoutes();
        }

        if (!$routes) {
            if ($configPath) {
                $this->collectFromFile($configPath);
            }
            if (!is_null($this->cacheTimeout)) {
                $routes = [
                    'named' => $this->_namedRoutes,
                    'all' => $this->_routes
                ];
                $this->_cacheDriver->set($cacheKey, $routes, $this->cacheTimeout);
            }
        }
    }

    /**
     * Set current handling route name
     * @param $name
     */
    public function setCurrentName($name)
    {
        $this->_currentName = $name;
    }

    /**
     * Get current handling route name
     * @return string
     */
    public function getCurrentName()
    {
        return $this->_currentName;
    }

    /**
     * Get current handling route namespace
     * @return string
     */
    public function getCurrentNamespace()
    {
        if ($name = $this->getCurrentName()) {
            if ($pos = mb_strrpos($name, ':', 0, 'UTF-8')) {
                return mb_substr($name, 0, $pos, 'UTF-8');
            }
        }
        return null;
    }

    /**
     * Retrieves all routes.
     * Useful if you want to process or display routes.
     * @return array All routes.
     */
    public function getRoutes()
    {
        return $this->_routes;
    }

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
    public function addRoutes($routes)
    {
        if (!is_array($routes) && !$routes instanceof Traversable) {
            throw new Exception('Routes should be an array or an instance of Traversable');
        }
        foreach ($routes as $route) {
            call_user_func_array(array($this, 'map'), $route);
        }
    }

    /**
     * Set the base path.
     * Useful if you are running your application from a subdirectory.
     * @param $basePath
     */
    public function setBasePath($basePath)
    {
        $this->_basePath = $basePath;
    }

    /**
     * Add named match types. It uses array_merge so keys can be overwritten.
     *
     * @param array $matchTypes The key is the name and the value is the regex.
     */
    public function addMatchTypes($matchTypes)
    {
        $this->_matchTypes = array_merge($this->_matchTypes, $matchTypes);
    }

    /**
     * Map a route to a target
     *
     * @param string $method One of 5 HTTP Methods, or a pipe-separated list of multiple HTTP Methods (GET|POST|PATCH|PUT|DELETE)
     * @param string $route The route regex, custom regex must start with an @. You can use multiple pre-set regex filters, like [i:id]
     * @param mixed $target The target where this route should point to. Can be anything.
     * @param string $name Optional name of this route. Supply if you want to reverse route this url in your application.
     * @throws Exception
     */
    public function map($method, $route, $target, $name = null)
    {
        if ($route == '') {
            $route = '/';
        }

        $this->_routes[] = array($method, $route, $target, $name);

        if ($name) {
            if (isset($this->_namedRoutes[$name])) {
                throw new \Exception("Can not redeclare route '{$name}'");
            } else {
                $this->_namedRoutes[$name] = $route;
            }
        }

        return;
    }

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
    public function url($routeName, $params = array())
    {
        if (Text::startsWith($routeName,':')) {
            $routeName = $this->getCurrentNamespace() . $routeName;
        }

        // Check if named route exists
        if (!isset($this->_namedRoutes[$routeName])) {
            throw new \Exception("Route '{$routeName}' does not exist.");
        }

        if (!is_array($params)) {
            $params = [$params];
        }
        // Replace named parameters
        $route = $this->_namedRoutes[$routeName];

        // prepend base path to route url again
        $url = $this->_basePath . $route;

        $matches = isset($this->_matched[$routeName]) ? $this->_matched[$routeName] : null;
        if (is_null($matches)) {
            preg_match_all('`(\/|)\{.*?:(.+?)\}(\?|)`', $route, $matches, PREG_SET_ORDER);
            $this->_matched[$routeName] = $matches;
            $this->setMatchedRoutes($this->_matched);
        }
        $usedParams = [];
        if ($matches) {
            $counter = 0;
            foreach ($matches as $match) {
                $param = $match[2];
                $block = $match[0];
                if ($match[1]) {
                    $block = substr($block, 1);
                }

                if (isset($params[$param])) {
                    $url = str_replace($block, $params[$param], $url);
                } elseif (isset($params[$counter])) {
                    $url = str_replace($block, $params[$counter], $url);
                } elseif ($match[3]) {
                    $url = str_replace($match[1] . $block, '', $url);
                } else {
                    throw new InvalidArgumentException('Incorrect params of route');
                }
                $usedParams[] = $param;
                $counter++;
            }
        }
        $query = [];
        foreach ($params as $param => $value) {
            if (is_string($param) && !in_array($param, $usedParams)) {
                $query[$param] = $value;
            }
        }

        return $url . ($query ? '?' . http_build_query($query) : '');
    }

    /**
     * Match a given Request Url against stored routes
     * @param string $requestUrl
     * @param string $requestMethod
     * @return array|boolean Array with route information on success, false on failure (no match).
     * @throws Exception
     */
    public function match($requestUrl = null, $requestMethod = null)
    {
        $this->eventTrigger('router.beforeMatch', [$requestUrl, $requestMethod], $this);

        $params = array();
        $match = false;

        // set Request Url if it isn't passed as parameter
        if ($requestUrl === null) {
            $requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        } elseif ($requestUrl === '') {
            $requestUrl = '/';
        }

        // strip base path from request url
        $requestUrl = substr($requestUrl, strlen($this->_basePath));

        // Strip query string (?a=b) from Request Url
        if (($strpos = strpos($requestUrl, '?')) !== false) {
            $requestUrl = substr($requestUrl, 0, $strpos);
        }

        // set Request Method if it isn't passed as a parameter
        if ($requestMethod === null) {
            $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        }

        $matches = [];
        $compiled = $this->getCompiledRoutes();
        $setCompiled = false;
        foreach ($this->_routes as $handler) {
            list($method, $_route, $target, $name) = $handler;

            $methods = explode('|', $method);
            $method_match = false;

            // Check if request method matches. If not, abandon early. (CHEAP)
            foreach ($methods as $method) {
                if (strcasecmp($requestMethod, $method) === 0) {
                    $method_match = true;
                    break;
                }
            }

            // Method did not match, continue to next route.
            if (!$method_match) continue;

            // Check for a wildcard (matches all)
            if ($_route === '*') {
                $match = true;
            } elseif (isset($_route[0]) && $_route[0] === '@') {
                $pattern = '`' . substr($_route, 1) . '`u';
                $match = preg_match($pattern, $requestUrl, $params);
            } else {
                $route = null;
                $regex = false;
                $j = 0;
                $n = isset($_route[0]) ? $_route[0] : null;
                $i = 0;

                // Find the longest non-regex substring and match it against the URI
                while (true) {
                    if (!isset($_route[$i])) {
                        break;
                    } elseif (false === $regex) {
                        $c = $n;
                        $regex = $c === '[' || $c === '(' || $c === '.';
                        if (false === $regex && false !== isset($_route[$i + 1])) {
                            $n = $_route[$i + 1];
                            $regex = $n === '?' || $n === '+' || $n === '*' || $n === '{';
                        }
                        if (false === $regex && $c !== '/' && (!isset($requestUrl[$j]) || $c !== $requestUrl[$j])) {
                            continue 2;
                        }
                        $j++;
                    }
                    $route .= $_route[$i++];
                }

                if (!isset($compiled[$route])) {
                    $setCompiled = true;
                    $compiled[$route] = $this->compileRoute($route);
                }
                $regex = $compiled[$route];
                $match = preg_match($regex, $requestUrl, $params);
            }

            if (($match == true || $match > 0)) {

                if ($params) {
                    foreach ($params as $key => $value) {
                        if (is_numeric($key)) unset($params[$key]);
                    }
                }

                $matches[] = array(
                    'target' => $target,
                    'params' => $params,
                    'name' => $name
                );
            }
        }
        if ($setCompiled) {
            $this->setCompiledRoutes($compiled);
        }

        if (!$matches && $requestUrl != '/' && $this->fixTrailingSlash && Text::endsWith($requestUrl, '/')) {
            /**
             * @TODO: kill me, please
             */
            Phact::app()->request->redirect(rtrim($requestUrl, '/'));
        }

        $this->eventTrigger('router.afterMatch', [$requestUrl, $requestMethod, $matches], $this);

        return $matches;
    }

    protected function getCompiledRoutes()
    {
        if (!$this->cacheTimeout) {
            return [];
        }
        return $this->_cacheDriver->get('PHACT__ROUTER_COMPILED');
    }

    protected function setCompiledRoutes($routes)
    {
        if (!$this->cacheTimeout) {
            return true;
        }
        $this->_cacheDriver->set('PHACT__ROUTER_COMPILED', $routes, $this->cacheTimeout);
        return true;
    }

    protected function getMatchedRoutes()
    {
        if (!$this->cacheTimeout) {
            return [];
        }
        return $this->_cacheDriver->get('PHACT__ROUTER_MATCHED', []);
    }

    /**
     * @param $routes
     * @return bool
     */
    protected function setMatchedRoutes($routes)
    {
        if (!$this->cacheTimeout) {
            return true;
        }
        $this->_cacheDriver->set('PHACT__ROUTER_MATCHED', $routes, $this->cacheTimeout);
        return true;
    }

    /**
     * Compile the regex for a given route (EXPENSIVE)
     * @param $route
     * @return string
     */
    protected function compileRoute($route)
    {
        if (preg_match_all('`(/|\.|)\{([^:\}]*+)(?::([^:\}]*+))?\}(\?|)`', $route, $matches, PREG_SET_ORDER)) {

            $matchTypes = $this->_matchTypes;
            foreach ($matches as $match) {
                list($block, $pre, $type, $param, $optional) = $match;

                if (isset($matchTypes[$type])) {
                    $type = $matchTypes[$type];
                }
                if ($pre === '.') {
                    $pre = '\.';
                }

                //Older versions of PCRE require the 'P' in (?P<named>)
                $pattern = '(?:'
                    . ($pre !== '' ? $pre : null)
                    . '('
                    . ($param !== '' ? "?P<$param>" : null)
                    . $type
                    . '))'
                    . ($optional !== '' ? '?' : null);

                $route = str_replace($block, $pattern, $route);
            }

        }
        return "`^$route$`u";
    }

    /**
     * Append routes from file
     *
     * @param $path
     * @throws Exception
     */
    public function collectFromFile($path)
    {
        if ($this->_path) {
            $routesPath = $this->_path->file($path, 'php');
            $routes = include $routesPath;
            $this->collect($routes);
        } else {
            throw new DependencyException('Required dependency ' . PathInterface::class . ' is not injected');
        }
    }

    /**
     * Append routes from array
     *
     * @param array $configuration
     * @param string $namespace
     * @param string $route
     * @throws Exception
     */
    public function collect($configuration = [], $namespace = '', $route = '')
    {
        foreach ($configuration as $item) {
            if (isset($item['route']) && isset($item['path'])) {
                $this->appendRoutes($item, $namespace, $route);
            } elseif (isset($item['route']) && isset($item['target'])) {
                $this->appendRoute($item, $namespace, $route);
            }
        }
    }

    /**
     * Append routes
     *
     * @param $item
     * @param string $namespace
     * @param string $route
     * @throws Exception
     */
    public function appendRoutes($item, $namespace = '', $route = '')
    {
        if (isset($item['path'])) {
            if (!$this->_path) {
                throw new DependencyException('Required dependency ' . PathInterface::class . ' is not injected');
            }
            $itemNamespace = isset($item['namespace']) ? $item['namespace'] : '';
            if ($itemNamespace && $namespace) {
                $itemNamespace = $namespace . ':' . $itemNamespace;
            }
            $path = isset($item['route']) ? $item['route'] : '';
            if ($path && $route) {
                $path = $route . $path;
            }

            $routesFile = $this->_path->file($item['path'], 'php');
            if (!$routesFile) {
                return;
            }
            $routes = include $routesFile;
            $this->collect($routes, $itemNamespace, $path);
        }
    }

    /**
     * Append single route
     * @param $item
     * @param string $namespace
     * @param string $route
     * @throws Exception
     */
    public function appendRoute($item, $namespace = '', $route = '/')
    {
        $methods = isset($item['methods']) ? $item['methods'] : ["GET", "POST"];
        $method = implode('|', $methods);
        $name = isset($item['name']) ? $item['name'] : '';
        if ($name && $namespace) {
            $name = $namespace . ':' . $name;
        }
        $path = isset($item['route']) ? $item['route'] : '';
        if ($route || $path) {
            $path = $route . $path;
        }
        $target = isset($item['target']) ? $item['target'] : null;
        $this->map($method, $path, $target, $name);
    }
}