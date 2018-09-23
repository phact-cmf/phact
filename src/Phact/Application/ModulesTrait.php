<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 22/09/2018 14:33
 */

namespace Phact\Application;


use Phact\Exceptions\InvalidConfigException;
use Phact\Exceptions\UnknownPropertyException;
use Phact\Helpers\Configurator;
use Phact\Module\Module;

/**
 * Modules management trait
 *
 * Trait ModulesTrait
 * @package Phact\Application
 */
trait ModulesTrait
{
    /**
     * Initialized modules
     *
     * @var Module[]
     */
    protected $_modules = [];

    /**
     * Modules configuration
     *
     * @var array
     */
    protected $_modulesConfig = [];

    /**
     * Set modules config
     *
     * @param array $config
     * @throws InvalidConfigException
     */
    public function setModules($config = [])
    {
        $this->_modulesConfig = $this->normalizeModulesConfig($config);
    }

    /**
     * Normalize modules configs
     *
     * @param $rawConfig
     * @return array
     * @throws InvalidConfigException
     */
    public function normalizeModulesConfig($rawConfig)
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

    /**
     * Return initialized instance of \Phact\Module\Module by name
     *
     * @param $name
     * @return mixed|Module
     * @throws InvalidConfigException
     * @throws UnknownPropertyException
     */
    public function getModule($name)
    {
        if (!isset($this->_modules[$name])) {
            $this->logDebug("Loading module '{$name}'");
            $config = $this->getModuleConfig($name);
            if (!is_null($config)) {
                $this->_modules[$name] = $this->_container->create($config['class']);
                unset($config['class']);
                foreach ($config as $property => $value) {
                    $this->_modules[$name]->{$property} = $value;
                }
            } else {
                throw new UnknownPropertyException("Module with name" . $name . " not found");
            }
        }

        return $this->_modules[$name];
    }

    /**
     * Get configuration of module
     *
     * @param $name
     * @return mixed|null
     */
    protected function getModuleConfig($name)
    {
        if (array_key_exists($name, $this->_modulesConfig)) {
            return $this->_modulesConfig[$name];
        }
        return null;
    }

    /**
     * Get modules names
     *
     * @return string[]
     */
    public function getModulesList()
    {
        return array_keys($this->getModulesConfig());
    }

    /**
     * Return list of modules classes by module name
     *
     * @return string[]
     */
    public function getModulesClasses()
    {
        $result = [];
        foreach ($this->getModulesConfig() as $name => $config) {
            $result[$name] = $config['class'];
        }
        return $result;
    }

    /**
     * Get modules configuration
     *
     * @return array
     */
    protected function getModulesConfig()
    {
        return $this->_modulesConfig;
    }
}