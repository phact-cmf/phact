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

class SlaveComponentByInterface
{
    /**
     * @var StandaloneComponentInterface
     */
    private $standaloneComponent;

    public function __construct(StandaloneComponentInterface $standaloneComponent)
    {
        $this->standaloneComponent = $standaloneComponent;
    }
}