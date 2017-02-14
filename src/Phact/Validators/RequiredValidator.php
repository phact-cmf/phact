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
 * @date 02/08/16 10:12
 */

namespace Phact\Validators;


class RequiredValidator extends Validator
{
    public function __construct($message = null)
    {
        if (!$message) {
            $message = 'Обязательно для заполнения';
        }
        $this->message = $message;
    }

    public function validate($value)
    {
        if (is_null($value) || $value === '') {
            return $this->message;
        }
        return true;
    }
}