<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 02/08/16 08:22
 */

namespace Phact\Form\Fields;

use Phact\Validators\EmailValidator;

class EmailField extends CharField
{
    /**
     * Required field
     * @var bool
     */
    public $emailErrorMessage = null;

    public function setDefaultValidators()
    {
        parent::setDefaultValidators();
        $this->_validators[] = new EmailValidator($this->emailErrorMessage);
    }
}