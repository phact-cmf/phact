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
use Phact\Helpers\Configurator;
use Phact\Helpers\Paths;
use Phact\Main\ComponentsLibrary;

class Application
{
    use ComponentsLibrary;
    
    public $name = 'Phact Application';

    public function __construct($config = [])
    {
        $this->configure($config);
        $this->setUpPaths();
    }

    public function configure($config = [])
    {
        Configurator::configure($this, $config);
    }

    public function setPaths($paths)
    {
        foreach ($paths as $name => $path) {
            Paths::add($name, $path);
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

        $modulesPath = Paths::get('modules');
        if (!$modulesPath) {
            $modulesPath = Paths::get('base.modules');
            Paths::add('modules', $modulesPath);
        }
        if (!is_dir($modulesPath)) {
            throw new InvalidConfigException('Modules path must be a valid. Please, set up correct modules path in "paths" section of configuration.');
        }
    }
}