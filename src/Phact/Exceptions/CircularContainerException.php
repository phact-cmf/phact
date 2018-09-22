<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 20/09/2018 19:10
 */

namespace Phact\Exceptions;


use Throwable;

class CircularContainerException extends ContainerException
{
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        $message .= ". Circular dependencies can be solved with calling setter with loaded service attribute.";
        parent::__construct($message, $code, $previous);
    }
}