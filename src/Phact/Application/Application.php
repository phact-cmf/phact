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

use Phact\Controller\Controller;
use Phact\Exceptions\InvalidConfigException;
use Phact\Exceptions\NotFoundHttpException;
use Phact\Exceptions\UnknownPropertyException;
use Phact\Helpers\Configurator;
use Phact\Helpers\Paths;
use Phact\Main\ComponentsLibrary;
use Phact\Request\HttpRequest;

/**
 * Class Application
 *
 * @property \Phact\Orm\ConnectionManager $db Database connection
 * @property \Phact\Router\Router $router Url manager, router
 * @property \Phact\Request\HttpRequest|\Phact\Request\CliRequest $request Request
 * @property \Phact\Request\Session $session Session
 * @property \Phact\Template\TemplateManager $template Template manager
 *
 * @package Phact\Application
 */
class Application
{
    use ComponentsLibrary;

    public $name = 'Phact Application';

    protected $_modules;
    protected $_modulesConfig;

    public function init()
    {
        $this->setUpPaths();
    }

    public function setPaths($paths)
    {
        foreach ($paths as $name => $path) {
            Paths::add($name, $path);
        }
    }

    public function setModules($config = [])
    {
        $this->_modulesConfig = $config;
    }

    public function getModule($name)
    {
        if (!isset($this->_modules[$name])) {
            $config = $this->getModuleConfig($name);
            if (!is_null($config)) {
                if (!isset($config['class'])) {
                    $config['class'] = '\\Modules\\' . ucfirst($name) . '\\' . ucfirst($name) . 'Module';
                }
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
        } elseif (in_array($name, $this->_modulesConfig)) {
            return [];
        } else {
            return null;
        }
    }

    public function getModules()
    {
        $list = [];
        foreach ($this->_modulesConfig as $key => $module) {
            if (is_string($key)) {
                $list[] = $key;
            } elseif (is_string($module)) {
                $list[] = $module;
            }
        }
        return $list;
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
        register_shutdown_function([$this, 'end'], 0);
        $this->handleRequest();
    }

    public function end($status = 0, $response = null)
    {
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

    public function handleWebRequest()
    {
        /** @var HttpRequest $request */
        $request = $this->request;
        $router = $this->router;
        $match = $router->match($request->getUrl(), $request->getMethod());
        if (!$match) {
            throw new NotFoundHttpException("Page not found");
        }

        if (is_array($match['target']) && isset($match['target'][0])) {
            $controllerClass = $match['target'][0];
            $action = isset($match['target'][1]) ? $match['target'][1] : null;
            $params = $match['params'];

            /** @var Controller $controller */
            $controller = new $controllerClass($this->request);
            $controller->run($action, $params);
        } elseif (is_callable($match['target'])) {
            $fn = $match['target'];
            $fn($this->request, $match['params']);
        }
    }

    public function handleCliRequest()
    {
    }
}