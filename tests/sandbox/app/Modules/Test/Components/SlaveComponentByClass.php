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

class SlaveComponentByClass
{
    /**
     * @var StandaloneComponent
     */
    private $standaloneComponent;

    public function __construct(StandaloneComponent $standaloneComponent)
    {
        $this->standaloneComponent = $standaloneComponent;
    }
}