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
 * @date 04/08/16 08:22
 */

namespace Phact\Module;


use Phact\Helpers\ClassNames;
use Phact\Helpers\SmartProperties;
use ReflectionClass;

abstract class Module
{
    protected static $_paths = [];

    use ClassNames, SmartProperties;

    public static function onApplicationInit()
    {
    }

    public static function onApplicationRun()
    {
    }

    public static function onApplicationEnd()
    {
    }

    public static function getVerboseName()
    {
        return static::getName();
    }

    public static function getName()
    {
        return str_replace('Module', '', static::classNameShort());
    }

    public static function getPath()
    {
        $class = static::class;
        if (!isset(static::$_paths[$class])) {
            $rc = new ReflectionClass($class);
            static::$_paths[$class] = dirname($rc->getFileName());
        }
        return static::$_paths[$class];
    }

    public static function getAdminMenu()
    {
        return [];
    }
}