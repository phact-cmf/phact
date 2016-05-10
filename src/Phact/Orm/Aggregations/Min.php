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


class Min extends Aggregation
{
    public function getSql($field)
    {
        return "MIN($field)";
    }
}