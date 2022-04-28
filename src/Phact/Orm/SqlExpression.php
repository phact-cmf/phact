<?php

namespace Phact\Orm;
/**
 *  Expression without aliases (raw expression)
 */
class SqlExpression extends Expression
{
    public function getAliases()
    {
        return [];
    }
}
