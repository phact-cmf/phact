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
 * @date 02/08/16 10:07
 */

namespace Phact\Validators;


use Phact\Helpers\SmartProperties;

abstract class Validator
{
    use SmartProperties;

    /**
     * Error message
     *
     * @var string
     */
    public $message = '';

    /**
     * Return (bool) true if value is valid
     * Return (string) message or (string[]) messages if value is invalid
     *
     * @param $value
     * @return mixed
     */
    abstract public function validate($value);
}