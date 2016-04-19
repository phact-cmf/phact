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
 * @date 18/04/16 08:30
 */

namespace Phact\Orm;

use InvalidArgumentException;

class Q
{
    public static function andQ($q)
    {
        return static::buildQ($q, 'and');
    }

    public static function orQ($q)
    {
        return static::buildQ($q, 'or');
    }

    public static function notQ($q)
    {
        return static::buildQ($q, 'not');
    }

    public static function buildQ($q, $condition)
    {
        if (!is_array($q)) {
            throw new InvalidArgumentException("Argument for methods andQ, orQ, notQ, buildQ must be an array");
        }
        return array_merge([$condition], $q);
    }
}