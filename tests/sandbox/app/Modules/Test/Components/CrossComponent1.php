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

class CrossComponent1
{
    /**
     * @var CrossComponent2
     */
    private $crossComponent2;

    public function __construct(CrossComponent2 $crossComponent2)
    {
        $this->crossComponent2 = $crossComponent2;
    }
}