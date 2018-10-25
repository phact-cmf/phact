<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 09/04/16 09:14
 */

namespace Phact\Application;

use Phact\Commands\Command;
use Phact\Components\PathInterface;
use Phact\Controller\ControllerInterface;
use Phact\Di\Container;
use Phact\Helpers\SmartProperties;
use Exception;
use Phact\Controller\Controller;
use Phact\Event\EventManager;
use Phact\Exceptions\InvalidConfigException;
use Phact\Exceptions\NotFoundHttpException;
use Phact\Helpers\Configurator;
use Phact\Interfaces\AuthInterface;
use Phact\Log\LoggerHandle;
use Phact\Request\CliRequest;
use Phact\Request\CliRequestInterface;
use Phact\Request\HttpRequestInterface;
use Phact\Router\RouterInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Application
 *
 * @property \Phact\Orm\ConnectionManager $db Database connection
 * @property \Phact\Router\Router $router Url manager, router
 * @property \Phact\Event\EventManager $event Event manager
 * @property \Phact\Request\HttpRequest|\Phact\Request\CliRequest $request Request
 * @property \Phact\Template\TemplateManager $template Template manager
 * @property \Phact\Interfaces\AuthInterface $auth Authorization component
 * @property \Phact\Cache\Cache $cache Cache component
 * @property \Phact\Translate\Translate $translate Translate component
 * @property \Phact\Components\Settings $settings Settings component
 * @property \Phact\Components\Breadcrumbs $breadcrumbs Breadcrumbs component
 * @property \Phact\Components\Flash $flash Flash component
 * @property \Phact\Components\Meta $meta Meta (SEO) component
 * @property $user
 * 
 * @package Phact\Application
 */
class Application implements ModulesInterface
{
    use SmartProperties, LoggerHandle, ModulesTrait;

    public $name = 'Phact Application';

    /**
     * @var Container
     */
    protected $_container;

    /**
     * Components config
     *
     * @var string
     */
    protected $_componentsConfig = [];

    /**
     * Container config
     *
     * @var string
     */
    protected $_containerConfig = [];

    /**
     * Autoload components names
     *
     * @var array
     */
    protected $_autoloadComponents = [];

    /**
     * Application initialization
     *
     * @throws InvalidConfigException
     * @throws \Phact\Exceptions\ContainerException
     */
    public function init()
    {
        $this->initContainer();
        $this->setUpPaths();
        $this->autoload();
        $this->initModules();
        $this->provideModuleEvent('onApplicationInit');
        $this->eventTrigger("application.afterInit", [], $this);
    }

    /**
     * Set autoload components config
     *
     * @param $components
     */
    public function setAutoloadComponents($components)
    {
        $this->_autoloadComponents = $components;
    }

    /**
     * Set components configuration for container
     *
     * @param $config
     */
    public function setComponents($config)
    {
        $this->_componentsConfig = $config;
    }

    /**
     * Set configuration for container
     *
     * @param $config
     */
    public function setContainer($config)
    {
        $this->_containerConfig = $config;
    }

    /**
     * Container initialization
     *
     * @throws InvalidConfigException
     * @throws \Phact\Exceptions\ContainerException
     */
    protected function initContainer()
    {
        if (!is_array($this->_containerConfig)) {
            throw new InvalidConfigException("Container config must be an array");
        }
        if (!isset($this->_containerConfig['class'])) {
            $this->_containerConfig['class'] = Container::class;
        }
        if (!is_a($this->_containerConfig['class'], Container::class, true)) {
            throw new InvalidConfigException("Container class must extend class " . Container::class);
        }
        $this->_container = Configurator::create($this->_containerConfig);
        $this->_container->set('application', $this);
        $this->_container->addReference('application', Application::class);
        $this->_container->addReference('application', ModulesInterface::class);
        $this->_container->setConfig($this->_componentsConfig);
    }

    /**
     * Autoload required application components
     */
    protected function autoload()
    {
        foreach ($this->_autoloadComponents as $name) {
            $this->_container->get($name);
        }
    }

    /**
     * Check and setup interfaces
     *
     * @throws InvalidConfigException
     */
    protected function setUpPaths()
    {
        /** @var PathInterface $paths */
        if ($this->_container->has(PathInterface::class) && ($paths = $this->_container->get(PathInterface::class))) {
            $basePath = $paths->get('base');
            if (!is_dir($basePath)) {
                throw new InvalidConfigException('Base path must be a valid directory. Please, set up correct base path in "paths" section of configuration.');
            }
            if (!$paths->get('runtime')) {
                $paths->add('runtime', $paths->get('base.runtime'));
            }
            if (!is_dir($paths->get('runtime')) || !is_writable($paths->get('runtime'))) {
                throw new InvalidConfigException('Runtime path must be a valid and writable directory. Please, set up correct runtime path in "paths" section of configuration.');
            }
            $modulesPath = $paths->get('Modules');
            if (!$modulesPath && ($modulesPath = $paths->get('base.Modules')) && is_dir($modulesPath)) {
                $paths->add('Modules', $modulesPath);
            }
            if (!is_dir($modulesPath)) {
                throw new InvalidConfigException('Modules path must be a valid. Please, set up correct modules path in "paths" section of configuration.');
            }
        }
    }

    public function run()
    {
        $this->logDebug("Application run");
        $this->eventTrigger("application.beforeRun", [], $this);
        $this->provideModuleEvent('onApplicationRun');
        register_shutdown_function([$this, 'end'], 0);
        $this->logDebug("Start handling request");
        $this->handleRequest();
        $this->end();
    }

    public function end($status = 0, $response = null)
    {
        $this->eventTrigger("application.beforeEnd", [], $this);
        $this->provideModuleEvent('onApplicationEnd', [$status, $response]);
        exit($status);
    }

    public function handleRequest()
    {
        if ($this->getIsWebMode()) {
            $this->handleWebRequest();
        } else {
            $this->handleCliRequest();
        }
    }

    /**
     * @return bool
     */
    public static function getIsCliMode()
    {
        return php_sapi_name() == 'cli';
    }

    /**
     * @return bool
     */
    public static function getIsWebMode()
    {
        return !self::getIsCliMode();
    }

    public function getUser()
    {
        /** @var AuthInterface $auth */
        if ($this->hasComponent(AuthInterface::class)) {
            return $this->getComponent(AuthInterface::class)->getUser();
        }
        return null;
    }

    public function handleWebRequest()
    {
        /** @var HttpRequestInterface $request */
        $request = $this->getComponent(HttpRequestInterface::class);
        /** @var RouterInterface $router */
        $router = $this->getComponent(RouterInterface::class);

        $url = $request->getUrl();
        $method = $request->getMethod();
        $this->logDebug("Matching route for url '{$url}' and method '{$method}'");
        $matches = $router->match($url, $method);

        foreach ($matches as $match) {
            $matched = $this->handleMatch($match, $router);
            if ($matched !== false) {
                return true;
            }
        }
        $this->logDebug("Matching route not found");
        throw new NotFoundHttpException("Page not found");
    }

    public function handleMatch(array $match, RouterInterface $router = null)
    {
        if (is_array($match['target']) &&
            isset($match['target'][0]) &&
            is_a($match['target'][0], ControllerInterface::class, true))
        {
            $controllerClass = $match['target'][0];
            $action = isset($match['target'][1]) ? $match['target'][1] : null;
            $params = isset($match['params']) ? $match['params'] : [];
            $name = isset($match['name']) ? $match['name'] : null;

            if ($router && $name) {
                $router->setCurrentName($name);
            }

            $this->logDebug("Processing route to controller '{$controllerClass}' and action '{$action}'", ['params' => $params]);
            /** @var Controller $controller */
            $controller = $this->_container->construct($controllerClass);
            $action = $action ?: $controller->defaultAction;

            $this->eventTrigger("application.beforeRunController", [$controller, $action, $name, $params], $this);
            $controller->beforeActionInternal($action, $params);
            $matched = $this->_container->invoke([$controller, $action], $params);
            $controller->afterActionInternal($action, $params, $matched);
            $this->eventTrigger("application.afterRunController", [$controller, $action, $name, $params, $matched], $this);
        } elseif (is_callable($match['target'])) {
            $fn = $match['target'];
            $matched = $fn($this->request, $match['params']);
        } else {
            $matched = false;
        }
        return $matched;
    }

    public function handleCliRequest()
    {
        /** @var CliRequest $request */
        $request = $this->getComponent(CliRequestInterface::class);
        $this->logDebug("Try to find command");

        if (!$request->isEmpty()) {
            list($class, $action, $arguments) = $request->match();
            /** @var Command $command */
            $command = $this->_container->construct($class);
            if (method_exists($command, $action)) {
                $this->logDebug("Run command '{$class}' action '{$action}'");
                $this->_container->invoke([$command, $action], [$arguments]);
            } else {
                throw new Exception("Method '{$action}' of class '{$class}' does not exist");
            }
        } else {
            $commandsConfigs = $request->getCommandsList();
            echo 'List of available commands' . PHP_EOL . PHP_EOL;
            foreach ($commandsConfigs as $class) {
                $command = $this->_container->construct($class);
                echo $command->getVerbose() . PHP_EOL;
            }
            echo PHP_EOL . 'Usage example:' . PHP_EOL . 'php index.php Base Db';
        }
    }

    /**
     * Get service from container object
     *
     * @param $name
     * @return object
     * @throws Exception
     */
    public function getComponent($name)
    {
        return $this->_container->get($name);
    }

    /**
     * Checks has container has service
     *
     * @param $name
     * @return bool
     */
    public function hasComponent($name)
    {
        return $this->_container->has($name);
    }

    /**
     * Set service/component
     *
     * @param $id
     * @param $service
     * @throws \Phact\Exceptions\ContainerException
     */
    public function setComponent($id, $service)
    {
        return $this->_container->set($id, $service);
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->_container;
    }

    /**
     * Magic get component or smart getter
     *
     * @param $name
     * @return object
     * @throws \Phact\Exceptions\UnknownPropertyException
     */
    public function __get($name)
    {
        if ($this->hasComponent($name)) {
            return $this->getComponent($name);
        } else {
            return $this->__smartGet($name);
        }
    }

    /**
     * Process event
     *
     * @param $name
     * @param array $params
     * @param null $sender
     * @param null $callback
     * @throws Exception
     */
    protected function eventTrigger($name, $params = array(), $sender = null, $callback = null)
    {
        if ($this->hasComponent(EventManager::class)) {
            $eventManager = $this->getComponent(EventManager::class);
            $eventManager->trigger($name, $params, $sender, $callback);
        }
    }

    /**
     * Call modules events
     *
     * @param $event
     * @param array $args
     */
    protected function provideModuleEvent($event, $args = [])
    {
        foreach ($this->getModules() as $name => $module) {
            call_user_func_array([$module, $event], $args);
        }
    }

    /**
     * @param string $name
     * @return LoggerInterface|null
     */
    public function getLogger($name = 'default')
    {
        $name = "logger.{$name}";
        if ($this->_container->has($name) && ($logger = $this->_container->get($name)) && ($logger instanceof LoggerInterface)) {
            return $logger;
        }
        return null;
    }
}