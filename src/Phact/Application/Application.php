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

use Phact\Helpers\Configurator;
use Phact\Main\ComponentsLibrary;

class Application
{
    use ComponentsLibrary;

    protected $_modulesPath;
    protected $_basePath;

    public $name = 'Phact Application';

    public function __construct($config = [])
    {
        $this->configure($config);
    }

    public function configure($config = [])
    {
        Configurator::configure($this, $config);
    }

    public function getModulesPath()
    {
        return $this->_modulesPath;
    }

    public function setModulesPath($path)
    {
        $this->_modulesPath = $path;
    }
}