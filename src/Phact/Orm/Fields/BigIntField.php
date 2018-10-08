<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 13/04/16 08:11
 */

namespace Phact\Orm\Fields;


class BigIntField extends IntField
{
    public $length = 20;
    public $unsigned = true;

    public function dbPrepareValue($value)
    {
        return (float) $value;
    }

    public function getType()
    {
        return 'bigint';
    }
}