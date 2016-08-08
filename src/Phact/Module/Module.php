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

abstract class Module
{
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

    public function getVerboseName()
    {
        return str_replace('Module', '', self::classNameShort());
    }
}