<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 15/09/2018 15:23
 */

namespace Phact\Orm\Adapters;


class PostgresqlAdapter
{
    /**
     * @return string
     */
    public static function getRegexpExpression()
    {
        return "~";
    }
}