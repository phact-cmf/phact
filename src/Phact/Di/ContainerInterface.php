<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 23/09/2018 10:44
 */

namespace Phact\Di;

use Phact\Exceptions\ContainerException;
use Phact\Exceptions\NotFoundContainerException;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionException;

interface ContainerInterface extends PsrContainerInterface
{
    /**
     * Add service
     *
     * @param $id
     * @param $service
     * @throws ContainerException
     */
    public function set($id, $service);

    /**
     * Check service is loaded
     *
     * @param $id
     * @return bool
     */
    public function loaded($id);

    /**
     * @param bool $fullReference
     */
    public function setFullReference(bool $fullReference);

    /**
     * @param bool $autowire
     */
    public function setAutowire(bool $autowire);

    /**
     * Set config of services
     *
     * @param array $config
     * @throws ContainerException
     */
    public function setConfig(array $config);

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
    public function setServices(array $definitions);

    /**
     * @param string $id
     * @param string|array $definition
     * @throws ContainerException
     */
    public function addDefinition(string $id, $definition);

    /**
     * Add reference by class name with given service id
     *
     * @param string $id
     * @param string $className
     */
    public function addReference(string $id, string $className);

    /**
     * Check that class name is referenced by service
     *
     * @param $className
     * @return bool
     */
    public function hasReference($className);

    /**
     * Get service by referenced class
     *
     * @param $className
     * @return mixed
     * @throws NotFoundContainerException
     */
    public function getByReference($className);

    /**
     * Invoke callable with dependency injection
     *
     * @param $callable
     * @param array $attributes
     * @return mixed
     * @throws ContainerException
     * @throws NotFoundContainerException
     * @throws ReflectionException
     */
    public function invoke($callable, $attributes = []);

    /**
     * Construct instance of class with constructor arguments
     *
     * @param string $className
     * @param $arguments array
     * @return object
     * @throws ContainerException
     * @throws NotFoundContainerException
     * @throws ReflectionException
     */
    public function construct(string $className, $arguments = []);
}