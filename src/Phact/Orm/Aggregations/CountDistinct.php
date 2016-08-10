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
 * @date 10/05/16 10:37
 */

namespace Phact\Orm\Aggregations;


class CountDistinct extends Aggregation
{
    public static function getSql($field)
    {
        return "COUNT(DISTINCT $field)";
    }
}