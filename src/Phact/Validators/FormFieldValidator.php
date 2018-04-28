<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 02/08/16 10:07
 */

namespace Phact\Validators;


use Phact\Form\Fields\Field;
use Phact\Helpers\SmartProperties;

abstract class FormFieldValidator extends Validator
{
    use SmartProperties;

    /**
     * @var Field
     */
    protected $_field;

    public function getField()
    {
        return $this->_field;
    }

    public function setField(Field $field)
    {
        $this->_field = $field;
    }
}