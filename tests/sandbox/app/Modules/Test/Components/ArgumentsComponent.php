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

class ArgumentsComponent
{
    /**
     * @var StandaloneComponent
     */
    private $standaloneComponent;
    /**
     * @var int
     */
    private $attribute;

    public function __construct(StandaloneComponent $standaloneComponent, $attribute = 3)
    {
        $this->standaloneComponent = $standaloneComponent;
        $this->attribute = $attribute;
    }

    public function getAttribute()
    {
        return $this->attribute;
    }
}