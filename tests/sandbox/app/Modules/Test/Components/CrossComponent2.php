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

class CrossComponent2
{
    /**
     * @var CrossComponent1
     */
    private $crossComponent1;

    public function __construct(CrossComponent1 $crossComponent1)
    {

        $this->crossComponent1 = $crossComponent1;
    }
}