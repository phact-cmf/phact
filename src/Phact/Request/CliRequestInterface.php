<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 23/09/2018 09:40
 */

namespace Phact\Request;


use Phact\Commands\Command;

interface CliRequestInterface
{
    /**
     * Check is empty request
     *
     * @return bool
     */
    public function isEmpty();

    /**
     * Get available commands list
     *
     * @return Command[]
     */
    public function getCommandsList();

    /**
     * Match command, returns array like [$className, $methodName, $methodArguments]
     * or throws an Exception
     *
     * @return array
     * @throws \Exception
     */
    public function match();
}