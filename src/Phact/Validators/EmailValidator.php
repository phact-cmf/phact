<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 02/08/16 10:12
 */

namespace Phact\Validators;


class EmailValidator extends Validator
{
    public function __construct($message = null)
    {
        if (!$message) {
            $message = 'Некорректный e-mail';
        }
        $this->message = $message;
    }

    public function validate($value)
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $this->message;
        }
        return true;
    }
}