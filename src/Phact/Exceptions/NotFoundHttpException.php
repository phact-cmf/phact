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
 * @date 09/04/16 09:40
 */

namespace Phact\Exceptions;

use Exception;

class NotFoundHttpException extends HttpException
{
    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        $this->status = 404;
        parent::__construct($message, $code, $previous);
    }
}