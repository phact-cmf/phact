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

class DateTimeField extends DateField
{
    /**
     * Value in the range from 0 to 6 may be given to specify fractional seconds precision
     * @var int
     */
    public $fsp = 0;

    public $format = 'Y-m-d H:i:s';

    public function getBlankValue()
    {
        return '0000-00-00 00:00:00';
    }

    public function getSqlType()
    {
        return "DATETIME({$this->fsp})";
    }
}