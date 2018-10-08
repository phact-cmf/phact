<?php
/**
 * Created by PhpStorm.
 * User: anton
 * Date: 03/07/2018
 * Time: 11:16
 */

namespace Phact\Event;

trait Events
{
    /**
     * @var EventManagerInterface
     */
    protected $_eventManager;

    /**
     * @param $name string Event name
     * @param array $params Params that passes to callback function
     * @param null $sender string|object|null Sender object or sender class name or null
     * @param null $callback callable|null Callback function that calls after event callback function and takes result of event callback function
     */
    protected function eventTrigger($name, $params = array(), $sender = null, $callback = null)
    {
        if ($this->_eventManager) {
            $this->_eventManager->trigger($name, $params, $sender, $callback);
        }
    }
}