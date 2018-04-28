<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 09/04/16 10:35
 */

namespace Phact\Helpers;

use Phact\Exceptions\UnknownPropertyException;

trait ClassNames
{
    public static function className()
    {
        return static::class;
    }

    public static function classNameShort()
    {
        return substr(static::class, strrpos(static::class, '\\')+1);
    }

    public static function classNameUnderscore()
    {
        return Text::camelCaseToUnderscores(static::classNameShort());
    }

    public static function getModuleName()
    {
        $classParts = explode('\\', static::class);
        if ($classParts[0] == 'Modules' && isset($classParts[1])) {
            return $classParts[1];
        }
        return null;
    }
}