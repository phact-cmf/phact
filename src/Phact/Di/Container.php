<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 20/09/2018 10:44
 */

namespace Phact\Di;

use Phact\Exceptions\CircularContainerException;
use Phact\Exceptions\ContainerException;
use Phact\Exceptions\NotFoundContainerException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;

class Container implements ContainerInterface
{
    const DEPENDENCY_VALUE = 1;

    const DEPENDENCY_OBJECT_VALUE_REQUIRED = 3;
    const DEPENDENCY_OBJECT_VALUE_OPTIONAL = 4;

    const DEPENDENCY_REFERENCE_REQUIRED = 5;
    const DEPENDENCY_REFERENCE_OPTIONAL = 6;
    const DEPENDENCY_REFERENCE_LOADED = 7;

    /**
     * Fetch references (Interfaces and Classes) from all given definitions with ReflectionClass
     *
     * @var bool
     */
    protected $_fullReference = true;

    /**
     * Automatically pass correct arguments to constructor by type-hint arguments with ReflectionClass
     *
     * @var bool
     */
    protected $_autowire = true;

    /**
     * Default service name, by class name
     *
     * @var array
     */
    protected $_bind = [];

    /**
     * Definitions of services, by name
     *
     * @var array
     */
    protected $_definitions = [];

    /**
     * Already reflected classes list
     *
     * @var array
     */
    protected $_processed = [];

    /**
     * Constructor dependencies, by class name
     *
     * @var array
     */
    protected $_constructors = [];

    /**
     * Services names, by class name
     *
     * @var array
     */
    protected $_references = [];

    /**
     * Class references (list of interfaces and ancestors classes) by class name
     *
     * @var array
     */
    protected $_classReferences = [];

    /**
     * Initialised services, by name
     *
     * @var array
     */
    protected $_services = [];

    /**
     * Currently loading elements, for circular dependencies prevention
     *
     * @var array
     */
    protected $_loading = [];

    public function __construct()
    {
        $this->addService('container', $this);
        $this->addReference(self::class, static::class, 'container');
        $this->addReference(ContainerInterface::class, static::class,'container');
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        if (isset($this->_services[$id])) {
            return $this->_services[$id];
        }
        if (!isset($this->_definitions[$id]) && isset($this->_references[$id])) {
            $id = reset($this->_references[$id]);
        }
        if (!isset($this->_definitions[$id])) {
            throw new NotFoundContainerException("There is no service with id {$id}");
        }
        return $this->build($id);
    }

    /**
     * Check entry is loaded
     *
     * @param $id
     * @return bool
     */
    public function loaded($id)
    {
        return isset($this->_services[$id]);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        return isset($this->_services[$id]) ||
            isset($this->_definitions[$id]) ||
            isset($this->_references[$id]);
    }

    /**
     * @param bool $fullReference
     */
    public function setFullReference(bool $fullReference)
    {
        $this->_fullReference = $fullReference;
    }

    /**
     * @param bool $autowire
     */
    public function setAutowire(bool $autowire)
    {
        $this->_autowire = $autowire;
    }

    /**
     * Set array of definitions
     *
     * Eg:
     *
     * [
     *     // Just class
     *     'request' => [
     *         'class' => \Phact\Request\HttpRequest
     *     ],
     *
     *     // Or
     *     'cli_request' => \Phact\Request\CliRequest,
     *
     *     // Class and arguments for constructor
     *     'router' => [
     *         'class' => \Phact\Router\Router,
     *         'arguments' => [
     *             'base.config.routes'
     *         ]
     *     ]
     *
     *     // And you can describe calls for calls methods after creation and properties for set up default properties
     *     'router' => [
     *         'class' => \MyAmazingComponent,
     *         'calls' => [
     *             'setRequest' => ['@request']
     *             'init' => ['Some string property for method init']
     *         ],
     *         'properties' => [
     *             'someProperty' => 'someValue'
     *         ]
     *     ]
     * ]
     *
     * @param array $definitions
     * @throws ContainerException
     */
    public function setServices(array $definitions)
    {
        foreach ($definitions as $id => $definition) {
            $this->addDefinition($id, $definition);
        }
    }

    /**
     * @param string $id
     * @param string|array $definition
     * @throws ContainerException
     */
    public function addDefinition(string $id, $definition)
    {
        if (is_string($definition)) {
            $definition = ['class' => $definition];
        }
        if (!is_array($definition)) {
            throw new ContainerException("Definition must be an array or a class name string");
        }
        if (!isset($definition['class'])) {
            throw new ContainerException("Definition must contain a class");
        }
        $options = [
            'arguments',
            'properties',
            'calls'
        ];
        foreach ($options as $option) {
            if (!isset($definition[$option])) {
                $definition[$option] = [];
            }
            if (!is_array($definition[$option])) {
                throw new ContainerException("Definition option {$option} must be an array");
            }
        }
        $this->_definitions[$id] = [
            'class' => $definition['class'],
            'arguments' => $definition['arguments'],
            'properties' => $definition['properties'],
            'calls' => $definition['calls']
        ];
        if ($this->_fullReference) {
            $this->addReferences($id, $definition['class']);
        }
    }


    /**
     * Add service to registered services
     *
     * @param string $id
     * @param $service
     * @throws ContainerException
     */
    protected function addService(string $id, $service)
    {
        if (isset($this->_services[$id])) {
            throw new ContainerException("Can not redeclare already registered service with name {$id}");
        }
        $this->_services[$id] = $service;
    }

    /**
     * Add reference
     *
     * @param string $classReference
     * @param string $ownerClassName
     * @param string $id
     */
    protected function addReference(string $classReference, string $ownerClassName, string $id)
    {
        if (!isset($this->_references[$classReference])) {
            $this->_references[$classReference] = [];
        }
        $this->_references[$classReference][] = $id;

        if (!isset($this->_classReferences[$ownerClassName])) {
            $this->_classReferences[$ownerClassName] = [];
        }
        $this->_classReferences[$ownerClassName][] = $classReference;
    }

    /**
     * Check that class name is referenced by service
     *
     * @param $className
     * @return bool
     */
    public function hasReference($className)
    {
        return isset($this->_references[$className]);
    }

    /**
     * Get service id by referenced class
     *
     * @param $className
     * @return mixed
     * @throws NotFoundContainerException
     */
    protected function getIdByReference($className)
    {
        if (!$this->hasReference($className)) {
            throw new NotFoundContainerException("There is no services that referenced with class {$className}");
        }
        return reset($this->_references[$className]);
    }

    /**
     * Get service by referenced class
     *
     * @param $className
     * @return mixed
     * @throws NotFoundContainerException
     */
    public function getByReference($className)
    {
        $id = $this->getIdByReference($className);
        return $this->get($id);
    }

    /**
     * Check fetched class references
     *
     * @param $className
     * @return bool
     */
    protected function hasClassReferences($className)
    {
        return isset($this->_classReferences[$className]);
    }

    /**
     * Get fetched class references
     *
     * @param $className
     * @return mixed
     * @throws ContainerException
     */
    protected function getClassReferences($className)
    {
        if (!$this->hasClassReferences($className)) {
            throw new ContainerException("References of class {$className} are unknown");
        }
        return $this->_classReferences[$className];
    }


    /**
     * Mark class as processed
     *
     * @param string $className
     */
    protected function markProcessed(string $className)
    {
        if (!$this->isProcessed($className)) {
            $this->_processed[$className] = true;
        }
    }

    /**
     * Mark class as processed
     *
     * @param string $className
     * @return bool
     */
    protected function isProcessed(string $className)
    {
        return isset($this->_processed[$className]);
    }


    /**
     * Add references of service
     *
     * @param string $id
     * @param string $className
     */
    protected function addReferences(string $id, string $className)
    {
        foreach ($this->fetchReferences($className) as $classReference) {
            $this->addReference($classReference, $className, $id);
        }
    }

    /**
     * Fetch references from class
     *
     * @param string $className
     * @return array
     */
    protected function fetchReferences(string $className)
    {
        if ($this->hasClassReferences($className)) {
            return $this->getClassReferences($className);
        } else {
            $references = [$className];
            try {
                $reflection = new ReflectionClass($className);
            } catch (ReflectionException $e) {
                $reflection = null;
            }
            if ($reflection) {
                $references = array_merge($references, $reflection->getInterfaceNames());
            }
            $classParents = class_parents($className);
            if ($classParents) {
                $references = array_merge($references, $classParents);
            }
            return $references;
        }
    }

    /**
     * Read constructor dependencies as array
     *
     * @param $className
     * @return array
     * @throws ReflectionException
     */
    protected function fetchConstructorDependencies($className)
    {
        if (!isset($this->_constructors[$className])) {
            $reflection = new ReflectionClass($className);
            $dependencies = [];
            $constructor = $reflection->getConstructor();
            if ($constructor) {
                foreach ($constructor->getParameters() as $param) {
                    if ($param->isVariadic()) {
                        break;
                    } elseif ($c = $param->getClass()) {
                        $type = self::DEPENDENCY_OBJECT_VALUE_REQUIRED;
                        if ($param->isOptional()) {
                            $type = self::DEPENDENCY_OBJECT_VALUE_OPTIONAL;
                        }
                        $dependencies[] = [
                            'type' => $type,
                            'value' => $c->getName()
                        ];
                    } elseif ($param->isDefaultValueAvailable()) {
                        $dependencies[] = [
                            'type' => self::DEPENDENCY_VALUE,
                            'value' => $param->getDefaultValue()
                        ];
                    } else {
                        $dependencies[] = [
                            'type' => self::DEPENDENCY_VALUE,
                            'value' => null
                        ];
                    }
                }
            }
            $this->_constructors[$className] = $dependencies;
        }
        return $this->_constructors[$className];
    }

    protected function build($id)
    {
        if (isset($this->_loading[$id])) {
            throw new CircularContainerException( "Circular dependency detected with services: " . implode(', ', array_keys($this->_loading)));
        }
        $this->_loading[$id] = true;

        $definition = $this->_definitions[$id];
        $className = $definition['class'];
        $object = $this->make($className, $definition['arguments'], $this->_autowire);
        $this->addService($id, $object);
        return $object;
    }

    protected function make(string $className, $config = [], $autowire = true)
    {
        $arguments = [];
        $dependencies = null;
        if ($autowire) {
            $dependencies = $this->fetchConstructorDependencies($className);
        }
        if ($dependencies) {
            foreach ($dependencies as $key => $dependency) {
                if (isset($config[$key])) {
                    list($type, $value) = $this->fetchAttributeValue($config[$key]);
                } else {
                    $type = $dependency['type'];
                    $value = $dependency['value'];
                }
                $arguments[] = $this->makeArgument($type, $value);
            }
        } else {
            foreach ($config as $key => $value) {
                list($type, $value) = $this->fetchAttributeValue($value);
                $arguments[] = $this->makeArgument($type, $value);
            }
        }
        if ($arguments) {
            return new $className(...$arguments);
        } else {
            return new $className;
        }
    }

    /**
     * @param $type
     * @param $value
     * @return mixed|null
     * @throws ContainerException
     * @throws NotFoundContainerException
     */
    protected function makeArgument($type, $value)
    {
        switch ($type) {
            case self::DEPENDENCY_VALUE;
                return $value;

            case self::DEPENDENCY_REFERENCE_REQUIRED;
                if ($this->has($value)) {
                    return $this->get($value);
                }
                throw new ContainerException("There is no service with id {$value} found");

            case self::DEPENDENCY_REFERENCE_LOADED:
                if ($this->loaded($value)) {
                    return $this->get($value);
                }
                return null;

            case self::DEPENDENCY_REFERENCE_OPTIONAL:
                if ($this->has($value)) {
                    return $this->get($value);
                }
                return null;

            case self::DEPENDENCY_OBJECT_VALUE_REQUIRED:
                if ($this->hasReference($value)) {
                    return $this->getByReference($value);
                }
                throw new ContainerException("There is no referenced classes of {$value} found");

            case self::DEPENDENCY_OBJECT_VALUE_OPTIONAL:
                if ($this->hasReference($value)) {
                    return $this->getByReference($value);
                }
                return null;
        }

        return null;
    }

    /**
     * Fetching attribute value
     *
     * @param $value
     * @return array
     */
    protected function fetchAttributeValue($value)
    {
        $type = self::DEPENDENCY_VALUE;
        if (\is_string($value) && 0 === strpos($value, '@')) {
            $type = self::DEPENDENCY_REFERENCE_REQUIRED;
            if (0 === strpos($value, '@!')) {
                $value = substr($value, 2);
                $type = self::DEPENDENCY_REFERENCE_LOADED;
            } elseif (0 === strpos($value, '@?')) {
                $value = substr($value, 2);
                $type = self::DEPENDENCY_REFERENCE_OPTIONAL;
            } else {
                $value = substr($value, 1);
            }
        }
        return [$type, $value];
    }
}