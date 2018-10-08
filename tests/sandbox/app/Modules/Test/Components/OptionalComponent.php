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

class OptionalComponent
{
    /**
     * @var StandaloneComponent|null
     */
    private $component;

    public function __construct(StandaloneComponent $component = null)
    {
        $this->component = $component;
    }

    /**
     * @return StandaloneComponent
     */
    public function getComponent()
    {
        return $this->component;
    }
}