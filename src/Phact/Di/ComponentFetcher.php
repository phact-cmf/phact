<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 04/10/2018 09:02
 */

namespace Phact\Di;

use Phact\Main\Phact;

/**
 * Safe fetch requirements
 *
 * Trait ComponentLoader
 * @package Phact\Di
 */
trait ComponentFetcher
{
    /**
     * @return Object|null
     */
    public static function fetchComponent($id)
    {
        if (($app = Phact::app()) && ($app->hasComponent($id))) {
            return $app->getComponent($id);
        }
        return null;
    }
}