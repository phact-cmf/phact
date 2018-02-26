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

use DateTime;

class DateField extends Field
{
    public $rawGet = true;

    public $rawSet = true;

    public $format = 'Y-m-d';

    /**
     * Automatically set the field to now when the object is first created
     * @var bool
     */
    public $autoNowAdd = false;

    /**
     * Automatically set the field to now every time the object is saved.
     * Useful for “last-modified” timestamps. Note that the current date is always used;
     * it’s not just a default value that you can override.
     * @var bool
     */
    public $autoNow = false;

    public function getBlankValue()
    {
        return '0000-00-00';
    }

    public function attributePrepareValue($value)
    {
        return $this->prepareDate($value);
    }

    public function getValue($aliasConfig = null)
    {
        return $this->_attribute;
    }

    public function dbPrepareValue($value)
    {
        return $this->prepareDate($value);
    }

    public function prepareDate($value)
    {
        if (is_int($value)) {
            $value = date($this->format, $value);
        } elseif (is_string($value)) {
            if (empty($value)) {
                $value = null;
            } elseif (!$this->isValidDateString($value)) {
                $time = strtotime($value);
                $value = date($this->format, $time);
            }
        } elseif ($value instanceof DateTime) {
            $value = $value->format($this->format);
        } else {
            $value = null;
        }
        return $value;
    }

    public function isValidDateString($date)
    {
        return DateTime::createFromFormat($this->format, $date) !== false;
    }

    public function getSqlType()
    {
        return "DATE";
    }

    public function beforeSave()
    {
        if ($this->autoNow) {
            $this->setValue(date($this->format));
        }

        if ($this->autoNowAdd) {
            $model = $this->getModel();
            if ($model->getIsNew()) {
                $this->setValue(date($this->format));
            }
        }
    }
}