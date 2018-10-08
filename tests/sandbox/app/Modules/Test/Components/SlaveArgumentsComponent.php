<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 19/05/16 07:48
 */

namespace Modules\Test\Components;

class SlaveArgumentsComponent
{
    /**
     * @var ArgumentsComponent
     */
    private $argumentsComponent;

    public function __construct(ArgumentsComponent $argumentsComponent)
    {

        $this->argumentsComponent = $argumentsComponent;
    }

    /**
     * @return ArgumentsComponent
     */
    public function getArgumentsComponent(): ArgumentsComponent
    {
        return $this->argumentsComponent;
    }
}