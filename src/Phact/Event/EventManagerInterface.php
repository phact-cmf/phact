<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 19/09/2018 17:41
 */

namespace Phact\Event;


interface EventManagerInterface
{
    public function on($name, $callback, $sender = null, $priority = 0);

    public function trigger($name, $params = array(), $sender = null, $callback = null);
}