<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 10/05/16 10:37
 */

namespace Phact\Orm\Aggregations;


class Avg extends Aggregation
{
    public static function getSql($field)
    {
        return "AVG($field)";
    }
}