<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 20/09/2018 12:19
 */

namespace Phact\Tests;

use Modules\Test\Components\ArgumentsComponent;
use Modules\Test\Components\CrossComponent1;
use Modules\Test\Components\CrossComponent2;
use Modules\Test\Components\OptionalComponent;
use Modules\Test\Components\SlaveArgumentsComponent;
use Modules\Test\Components\SlaveComponentByClass;
use Modules\Test\Components\SlaveComponentByInterface;
use Modules\Test\Components\StandaloneComponent;
use Phact\Di\Container;
use Phact\Exceptions\CircularContainerException;

class ContainerTest extends TestCase
{
    public function testCreate()
    {
        $container = new Container();
        $this->assertInstanceOf(Container::class, $container);
        $this->assertTrue($container->has('container'));
        $this->assertEquals($container, $container->get('container'));
    }

    public function testSimpleServiceDefinition()
    {
        $container = new Container();
        $container->addDefinition('standalone', StandaloneComponent::class);
        $standaloneComponent = $container->get('standalone');
        $this->assertInstanceOf(StandaloneComponent::class, $standaloneComponent);
        $this->assertEquals($standaloneComponent, $container->get('standalone'));
    }

    public function testDependencyByClass()
    {
        $container = new Container();
        $container->addDefinition('standalone', StandaloneComponent::class);
        $container->addDefinition('slave', SlaveComponentByClass::class);
        $slaveComponent = $container->get('slave');
        $this->assertInstanceOf(SlaveComponentByClass::class, $slaveComponent);
    }

    public function testArguments()
    {
        $container = new Container();
        $container->addDefinition('standalone', StandaloneComponent::class);
        $container->addDefinition('attributed', [
            'class' => ArgumentsComponent::class,
            'arguments' => [
                1 => 5
            ]
        ]);
        /** @var ArgumentsComponent $component */
        $component = $container->get('attributed');
        $this->assertEquals(5, $component->getAttribute());
    }

    public function testDefaultArguments()
    {
        $container = new Container();
        $container->addDefinition('standalone', StandaloneComponent::class);
        $container->addDefinition('attributed', [
            'class' => ArgumentsComponent::class
        ]);
        /** @var ArgumentsComponent $component */
        $component = $container->get('attributed');
        $this->assertEquals(3, $component->getAttribute());
    }

    public function testReference()
    {
        $container = new Container();
        $container->addDefinition('standalone', StandaloneComponent::class);
        $container->addDefinition('attributed5', [
            'class' => ArgumentsComponent::class,
            'arguments' => [
                1 => 5
            ]
        ]);
        $container->addDefinition('attributed3', [
            'class' => ArgumentsComponent::class,
            'arguments' => [
                1 => 3
            ]
        ]);
        $container->addDefinition('attributed_slave3', [
            'class' => SlaveArgumentsComponent::class,
            'arguments' => [
                "@attributed3"
            ]
        ]);
        $container->addDefinition('attributed_slave5', [
            'class' => SlaveArgumentsComponent::class,
            'arguments' => [
                "@attributed5"
            ]
        ]);
        /** @var SlaveArgumentsComponent $component */
        $component3 = $container->get('attributed_slave3');
        $component5 = $container->get('attributed_slave5');
        $this->assertEquals(3, $component3->getArgumentsComponent()->getAttribute());
        $this->assertEquals(5, $component5->getArgumentsComponent()->getAttribute());
    }

    public function testNotLoaded()
    {
        $container = new Container();
        $container->addDefinition('standalone', StandaloneComponent::class);
        $container->addDefinition('optional', [
            'class' => OptionalComponent::class,
            'arguments' => [
                '@!standalone'
            ]
        ]);
        /** @var OptionalComponent $component */
        $component = $container->get('optional');
        $this->assertNull($component->getComponent());
    }

    public function testLoaded()
    {
        $container = new Container();
        $container->addDefinition('standalone', StandaloneComponent::class);
        $container->addDefinition('optional', [
            'class' => OptionalComponent::class,
            'arguments' => [
                '@!standalone'
            ]
        ]);
        $standalone = $container->get('standalone');
        /** @var OptionalComponent $component */
        $component = $container->get('optional');
        $this->assertInstanceOf(StandaloneComponent::class, $component->getComponent());
    }

    public function testOptionalExists()
    {
        $container = new Container();
        $container->addDefinition('standalone', StandaloneComponent::class);
        $container->addDefinition('optional', [
            'class' => OptionalComponent::class,
            'arguments' => [
                '@?standalone'
            ]
        ]);
        /** @var OptionalComponent $component */
        $component = $container->get('optional');
        $this->assertInstanceOf(StandaloneComponent::class, $component->getComponent());
    }

    public function testOptionalNotExists()
    {
        $container = new Container();
        $container->addDefinition('optional', [
            'class' => OptionalComponent::class,
            'arguments' => [
                '@?standalone'
            ]
        ]);
        /** @var OptionalComponent $component */
        $component = $container->get('optional');
        $this->assertNull($component->getComponent());
    }

    /**
     * @expectedException \Phact\Exceptions\CircularContainerException
     */
    public function testCross()
    {
        $container = new Container();
        $container->addDefinition('cross2', CrossComponent2::class);
        $container->addDefinition('cross1', CrossComponent1::class);
        $container->get('cross1');
    }
}