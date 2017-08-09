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
 * @date 09/04/16 09:14
 */

namespace Phact\Application;

use Exception;
use Phact\Controller\Controller;
use Phact\Exceptions\InvalidConfigException;
use Phact\Exceptions\NotFoundHttpException;
use Phact\Exceptions\UnknownPropertyException;
use Phact\Helpers\Configurator;
use Phact\Helpers\Paths;
use Phact\Interfaces\AuthInterface;
use Phact\Main\ComponentsLibrary;
use Phact\Request\CliRequest;
use Phact\Request\HttpRequest;

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
 * @property \Phact\Components\Settings $settings Settings component
 * @property \Phact\Components\Breadcrumbs $breadcrumbs Breadcrumbs component
 * @property \Phact\Components\Flash $flash Flash component
 * @property \Phact\Components\Meta $meta Meta (SEO) component
 * @property $user
 * 
 * @package Phact\Application
 */
class Application
{
    use ComponentsLibrary;

    public $name = 'Phact Application';

    protected $_modules = [];
    protected $_modulesConfig = [];

    public $autoloadComponents = [];

    public function init()
    {
        $this->_provideModuleEvent('onApplicationInit');
        $this->setUpPaths();
        $this->autoload();
    }

    public function setPaths($paths)
    {
        foreach ($paths as $name => $path) {
            Paths::add($name, $path);
        }
    }

    public function autoload()
    {
        foreach ($this->autoloadComponents as $name) {
            $this->getComponent($name);
        }
    }

    public function setModules($config = [])
    {
        $this->_modulesConfig = $this->prepareModulesConfigs($config);
    }

    public function prepareModulesConfigs($rawConfig)
    {
        $configs = [];
        foreach ($rawConfig as $key => $module) {
            $name = null;
            $config = [];
            if (is_string($module)) {
                $name = $module;
            } elseif (is_string($key)) {
                $name = $key;
                if (is_array($module)) {
                    $config = $module;
                }
            } else {
                throw new InvalidConfigException("Unable to configure module {$key}");
            }

            $name = ucfirst($name);
            $class = '\\Modules\\' . $name . '\\' . $name . 'Module';
            $config['class'] = $class;
            $configs[$name] = $config;
        }
        return $configs;
    }

    public function getModule($name)
    {
        if (!isset($this->_modules[$name])) {
            $config = $this->getModuleConfig($name);
            if (!is_null($config)) {
                $this->_modules[$name] = Configurator::create($config);
            } else {
                throw new UnknownPropertyException("Module with name" . $name . " not found");
            }
        }

        return $this->_modules[$name];
    }

    public function getModuleConfig($name)
    {
        if (array_key_exists($name, $this->_modulesConfig)) {
            return $this->_modulesConfig[$name];
        }
        return null;
    }

    public function getModulesList()
    {
        return array_keys($this->_modulesConfig);
    }

    public function getModulesConfig()
    {
        return $this->_modulesConfig;
    }

    protected function _provideModuleEvent($event, $args = [])
    {
        foreach ($this->_modulesConfig as $name => $config) {
            $class = $config['class'];
            forward_static_call_array([$class, $event], $args);
        }
    }

    public function setUpPaths()
    {
        $basePath = Paths::get('base');
        if (!is_dir($basePath)) {
            throw new InvalidConfigException('Base path must be a valid directory. Please, set up correct base path in "paths" section of configuration.');
        }

        $runtimePath = Paths::get('runtime');
        if (!$runtimePath) {
            $runtimePath = Paths::get('base.runtime');
            Paths::add('runtime', $runtimePath);
        }
        if (!is_dir($runtimePath) || !is_writable($runtimePath)) {
            throw new InvalidConfigException('Runtime path must be a valid and writable directory. Please, set up correct runtime path in "paths" section of configuration.');
        }

        $modulesPath = Paths::get('Modules');
        if (!$modulesPath) {
            $modulesPath = Paths::get('base.Modules');
            Paths::add('Modules', $modulesPath);
        }
        if (!is_dir($modulesPath)) {
            throw new InvalidConfigException('Modules path must be a valid. Please, set up correct modules path in "paths" section of configuration.');
        }
    }

    public function run()
    {
        $this->_provideModuleEvent('onApplicationRun');
        register_shutdown_function([$this, 'end'], 0);
        $this->handleRequest();
        $this->end();
    }

    public function end($status = 0, $response = null)
    {
        $this->_provideModuleEvent('onApplicationEnd', [$status, $response]);
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
        if ($this->hasComponent('auth')) {
            return $this->getComponent('auth')->getUser();
        }
        return null;
    }
    public function handleWebRequest()
    {
        /** @var HttpRequest $request */
        $request = $this->request;
        $router = $this->router;
        $matches = $router->match($request->getUrl(), $request->getMethod());
        foreach ($matches as $match) {
            if (is_array($match['target']) && isset($match['target'][0])) {
                $controllerClass = $match['target'][0];
                $action = isset($match['target'][1]) ? $match['target'][1] : null;
                $params = $match['params'];

                /** @var Controller $controller */
                $controller = new $controllerClass($this->request);
                $matched = $controller->run($action, $params);
                if ($matched !== false) {
                    return true;
                }
            } elseif (is_callable($match['target'])) {
                $fn = $match['target'];
                $matched = $fn($this->request, $match['params']);
                if ($matched !== false) {
                    return true;
                }
            }
        }
        throw new NotFoundHttpException("Page not found");
    }

    public function handleCliRequest()
    {
        /** @var CliRequest $request */
        $request = $this->request;
        list($module, $command, $action, $arguments) = $request->parse();
        if ($module && $command) {
            $module = ucfirst($module);
            $command = ucfirst($command);
            $class = '\\Modules\\' . $module . '\\Commands\\' . $command . 'Command';
            if (class_exists($class)) {
                $command = new $class();
                if (method_exists($command, $action)) {
                    $command->{$action}($arguments);
                } else {
                    throw new Exception("Method '{$action}' of class '{$class}' does not exist");
                }
            } else {
                throw new Exception("Class '{$class}' does not exist");
            }
        } else {
            $data = $request->getCommandsList();
            echo 'List of available commands' . PHP_EOL . PHP_EOL;
            foreach ($data as $name => $commands) {
                echo 'Module: ' . $name . PHP_EOL;
                foreach ($commands as $command => $description) {
                    echo $command . ($description ? ' - '. $description : '') . PHP_EOL;
                }
                echo PHP_EOL;
            }
            echo  'Usage example:' . PHP_EOL . 'php index.php Base Db';
        }
    }
}