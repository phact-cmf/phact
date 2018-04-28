<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 02/02/17 12:09
 */

namespace Phact\Cache;


use Phact\Helpers\Configurator;
use Phact\Helpers\SmartProperties;

class Cache
{
    use SmartProperties;

    protected $_config = [];

    protected $_drivers = [];
    
    public $defaultDriver = 'default';
    
    public function setDrivers($config)
    {
        $this->_config = $config;
    }

    /**
     * @param string $name
     * @return CacheDriver|null
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    public function getDriver($name = 'default')
    {
        if (!isset($this->_drivers[$name])) {
            if (isset($this->_config[$name])) {
                $this->_drivers[$name] = Configurator::create($this->_config[$name]);
            } else {
                return null;
            }
        }
        return $this->_drivers[$name];
    }
    
    public function set($key, $value, $timeout = null)
    {
        return $this->getDriver($this->defaultDriver)->set($key, $value, $timeout);
    }

    public function get($key, $default = null)
    {
        return $this->getDriver($this->defaultDriver)->get($key, $default);
    }
}