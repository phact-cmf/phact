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

class RequiredValidator extends Validator
{
    use Translator;

    public function __construct($message = null)
    {
        if (!$message) {
            $message = self::t('Phact.validators', 'This field is required');
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