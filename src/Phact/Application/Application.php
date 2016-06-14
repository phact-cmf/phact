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

use Phact\Exceptions\InvalidConfigException;
use Phact\Exceptions\UnknownPropertyException;
use Phact\Helpers\Configurator;
use Phact\Helpers\Paths;
use Phact\Main\ComponentsLibrary;
use Phact\Orm\ConnectionManager;

/**
 * Class Application
 *
 * @property \Phact\Orm\ConnectionManager $db Database connection.
 * @property \Phact\Router\Router $router Url manager, router.
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
            if (isset($this->_modulesConfig[$name])) {
                $config = $this->_modulesConfig[$name];
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
}