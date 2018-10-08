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

class TimeField extends DateTimeField
{
    public $format = 'H:i:s';

    public function getBlankValue()
    {
        return '00:00:00';
    }

    public function getSqlType()
    {
        return "time";
    }
}