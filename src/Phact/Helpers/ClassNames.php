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
 * @date 09/04/16 10:35
 */

namespace Phact\Helpers;

use Phact\Exceptions\UnknownPropertyException;

trait ClassNames
{
    public static function className()
    {
        return get_called_class();
    }

    public static function classNameShort()
    {
        $class = get_called_class();
        $classParts = explode('\\', $class);
        return array_pop($classParts);
    }

    public static function classNameUnderscore()
    {
        return Text::camelCaseToUnderscores(static::classNameShort());
    }
}