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


use Phact\Translate\Translator;

class EmailValidator extends Validator
{
    use Translator;

    public function __construct($message = null)
    {
        if (!$message) {
            $message = self::t('Phact.validators', 'Incorrect e-mail');
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