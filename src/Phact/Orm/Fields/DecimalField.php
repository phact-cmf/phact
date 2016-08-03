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
 * @date 13/04/16 08:11
 */

namespace Phact\Orm\Fields;


class DecimalField extends NumericField
{
    /**
     * Total number of digits
     * @var int
     */
    public $precision = 10;

    /**
     * Number of digits after the decimal point
     * @var int
     */
    public $scale = 2;

    public function getValue($aliasConfig = null)
    {
        return is_null($this->_attribute) ? null : (float) $this->_attribute;
    }

    public function dbPrepareValue($value)
    {
        return (float) $value;
    }

    public function mainSqlType()
    {
        return "DECIMAL({$this->precision}, {$this->scale})";
    }
}