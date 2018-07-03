<?php
/**
 * Created by PhpStorm.
 * User: anton
 * Date: 03/07/2018
 * Time: 11:16
 */

namespace Phact\Event;

use Phact\Main\Phact;

trait Events
{
    /**
     * @param $name
     * @return null|EventManager
     */
    public static function getEventManager()
    {
        if (Phact::app()->hasComponent('event')) {
            return Phact::app()->getComponent('event');
        }
        return null;
    }

    /**
     * @param $name string Event name
     * @param array $params Params that passes to callback function
     * @param null $sender string|object|null Sender object or sender class name or null
     * @param null $callback callable|null Callback function that calls after event callback function and takes result of event callback function
     */
    public static function eventTrigger($name, $params = array(), $sender = null, $callback = null)
    {
        $manager = static::getEventManager();
        if ($manager) {
            $manager->trigger($name, $params, $sender, $callback);
        }
    }
}