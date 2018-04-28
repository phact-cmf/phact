<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 09/04/16 09:40
 */

namespace Phact\Exceptions;

use Exception;

class HttpException extends Exception
{
    /**
     * @var int status code (404, 403 ...)
     */
    public $status;

    public $defaultMessages = [
        404 => 'Page not found'
    ];

    public function __construct($status, $message = null, $code = 0, Exception $previous = null)
    {
        $this->status = $status;
        if (!$message && array_key_exists($status, $this->defaultMessages)) {
            $message = $this->defaultMessages[$status];
        }
        parent::__construct($message, $code, $previous);
    }
}