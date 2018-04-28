<?php

/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 09/04/16 11:26
 */

namespace Phact\Tests;

use Phact\Event\EventManager;

class EventsTest extends AppTest
{
    public function testSimple()
    {
        $this->expectOutputString('Test');
        $manager = new EventManager();
        $manager->on('testEvent', function() {
            echo 'Test';
        });
        $manager->trigger('testEvent');
    }

    public function testParams()
    {
        $this->expectOutputString('Test');
        $manager = new EventManager();
        $manager->on('testEvent', function($sender, $text) {
            echo $text;
        });
        $manager->trigger('testEvent', ['Test']);
    }

    public function testSender()
    {
        $this->expectOutputString('InstanceSame');
        $manager = new EventManager();
        $class = self::class;
        $manager->on('testEvent', function($sender, $text) use ($class) {
            if (is_a($sender, $class)) {
                echo 'Instance';
            }
        });
        $manager->on('testEvent', function($sender, $text) use ($class) {
            if (is_a($sender, $class)) {
                echo 'Same';
            }
        }, self::class);
        $manager->on('testEvent', function($sender, $text) {
            echo 'Another';
        }, EventManager::class);
        $manager->trigger('testEvent', ['Test'], $this);
    }

    public function testCallback()
    {
        $this->expectOutputString('Test');
        $manager = new EventManager();
        $manager->on('testEvent', function($sender, $text) {
            return $text;
        });
        $manager->trigger('testEvent', ['Test'], $this, function($text){
            echo $text;
        });
    }
}