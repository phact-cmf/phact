<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 23/09/2018 13:20
 */

namespace Phact\Tests;


use Phact\Commands\Command;
use Phact\Request\CliRequestInterface;

class CliTestRequest implements CliRequestInterface
{
    public $empty = false;

    public $commandsList = [];

    public $match = [];

    /**
     * Check is empty request
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->empty;
    }

    /**
     * Get available commands list
     *
     * @return Command[]
     */
    public function getCommandsList()
    {
        return $this->commandsList;
    }

    /**
     * Match command, returns array like [$className, $methodName, $methodArguments]
     * or throws an Exception
     *
     * @return array
     * @throws \Exception
     */
    public function match()
    {
        return $this->match;
    }
}