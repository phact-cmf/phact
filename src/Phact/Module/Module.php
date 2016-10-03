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
    protected static $_path;

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
        if (!static::$_path) {
            $rc = new ReflectionClass(static::class);
            static::$_path = dirname($rc->getFileName());
        }
        return static::$_path;
    }

    public static function getAdminMenu()
    {
        return [];
    }
}