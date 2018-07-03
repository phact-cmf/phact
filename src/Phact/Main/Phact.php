<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 10/04/16 10:20
 */

namespace Phact\Main;

use Phact\Application\Application;
use Phact\Helpers\Configurator;

class Phact
{
    protected static $_app;

    public static function init($configuration, $application = Application::class)
    {

        static::$_app = new $application;
        Configurator::configure(static::$_app, $configuration);
        static::$_app->init();
    }

    /**
     * @return \Phact\Application\Application
     */
    public static function app()
    {
        return static::$_app;
    }
}