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

class SlaveSetterComponent
{
    /**
     * @var StandaloneComponentInterface
     */
    private $standaloneComponent;

    private $value;

    public function setStandalone(StandaloneComponentInterface $standaloneComponent)
    {
        $this->standaloneComponent = $standaloneComponent;
    }

    public function setStandaloneAndSomething(StandaloneComponentInterface $standaloneComponent, $someValue)
    {
        $this->standaloneComponent = $standaloneComponent;
        $this->value = $someValue;
    }

    public function setStandaloneOptional(StandaloneComponentInterface $standaloneComponent = null)
    {
        $this->standaloneComponent = $standaloneComponent;
    }

    public function getStandalone()
    {
        return $this->standaloneComponent;
    }

    public function getValue()
    {
        return $this->value;
    }
}