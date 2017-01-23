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


class EmailValidator extends Validator
{
    public function __construct($message = null)
    {
        if (!$message) {
            $message = 'E-mail is invalid';
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