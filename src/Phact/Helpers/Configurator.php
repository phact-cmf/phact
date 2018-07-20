<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 09/04/16 09:31
 */

namespace Phact\Helpers;
use Phact\Exceptions\InvalidConfigException;

/**
 * Helper class that create objects and configure it
 *
 * Class Configurator
 * @package Phact\Helpers
 */
class Configurator
{
    /**
     *
     * @param $class string|array
     * @param array $config array
     * @return mixed
     * @throws InvalidConfigException
     */
    public static function create($class, $config = [])
    {
        list($class, $config) = self::split($class, $config);
        if (isset($config['__construct']) && is_array($config['__construct'])) {
            $obj = new $class(...$config['__construct']);
            unset($config['__construct']);
        } else {
            $obj = new $class;
        }
        if (isset($config['__afterConstruct']) && is_callable($config['__afterConstruct'])) {
            call_user_func($config['__afterConstruct'], $obj);
            unset($config['__afterConstruct']);
        }
        $obj = self::configure($obj, $config);
        if (method_exists($obj, 'init')) {
            $obj->init();
        }
        return $obj;
    }

    public static function split($class, $config = [])
    {
        if (is_array($class) && isset($class['class'])) {
            $config = $class;
            $class = $config['class'];
            unset($config['class']);
        } elseif (!is_string($class)) {
            throw new InvalidConfigException("Class name must be defined");
        }
        return [$class, $config];
    }

    public static function configure($object, $properties)
    {
        foreach ($properties as $name => $value) {
            $object->{$name} = $value;
        }
        return $object;
    }
}