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

use Phact\Components\PathInterface;
use Phact\Exceptions\InvalidConfigException;
use Phact\Exceptions\UnknownPropertyException;
use Phact\Helpers\Configurator;
use Phact\Main\Phact;
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
     * @var Module[]
     */
    protected $_modules = [];

    /**
     * Modules config
     * @var array
     */
    protected $_modulesConfig = [];

    /**
     * Set modules config
     * @param array $config
     * @throws InvalidConfigException
     */
    public function setModules($config = [])
    {
        $this->_modulesConfig = $this->normalizeModulesConfig($config);
    }

    /**
     * Normalize modules configs
     * @param $rawConfig
     * @return array
     * @throws InvalidConfigException
     */
    protected function normalizeModulesConfig($rawConfig)
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
     * Modules initialization
     */
    protected function initModules()
    {
        $this->eventTrigger('application.beforeModulesInit', [], $this);
        foreach ($this->_modulesConfig as $name => $config) {
            $this->initModule($name, $config);
        }
        $this->eventTrigger('application.afterModulesInit', [], $this);
    }

    /**
     * Module initialization
     * @param $name
     * @param $config
     */
    protected function initModule($name, $config)
    {
        $this->_modules[$name] = $this->_container->construct($config['class'], [$name]);
        if ($this->_container->has(PathInterface::class) && ($paths = $this->_container->get(PathInterface::class))) {
            $paths->add("Modules.{$name}", $this->_modules[$name]->getPath());
        }
        unset($config['class']);
        foreach ($config as $property => $value) {
            $this->_modules[$name]->{$property} = $value;
        }
        $this->eventTrigger('module.afterInit', [], $this->_modules[$name]);
    }

    /**
     * Return initialized instance of \Phact\Module\Module by name
     * @param $name
     * @return mixed|Module
     * @throws InvalidConfigException
     * @throws UnknownPropertyException
     */
    public function getModule($name)
    {
        if (!isset($this->_modules[$name])) {
            throw new UnknownPropertyException("Module with name" . $name . " not found");
        }
        return $this->_modules[$name];
    }

    /**
     * All initialized modules
     * @return Module[]
     */
    public function getModules()
    {
        return $this->_modules;
    }

    /**
     * Get modules names
     * @return string[]
     */
    public function getModulesList()
    {
        return array_keys($this->_modules);
    }
}