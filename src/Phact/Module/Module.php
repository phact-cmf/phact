<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company HashStudio
 * @site http://hashstudio.ru
 * @date 04/08/16 08:22
 */

namespace Phact\Module;


use Phact\Cache\Cache;
use Phact\Form\ModelForm;
use Phact\Helpers\ClassNames;
use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;
use Phact\Orm\Model;
use ReflectionClass;

abstract class Module
{
    protected static $_paths = [];

    use ClassNames, SmartProperties;

    public $settingsModelCache = 3600;

    public static function onApplicationInit()
    {
    }

    public static function onApplicationRun()
    {
    }

    public static function onApplicationEnd()
    {
    }

    public static function getVerboseName()
    {
        return static::getName();
    }

    public static function getName()
    {
        return str_replace('Module', '', static::classNameShort());
    }

    public static function getPath()
    {
        $class = static::class;
        if (!isset(static::$_paths[$class])) {
            $rc = new ReflectionClass($class);
            static::$_paths[$class] = dirname($rc->getFileName());
        }
        return static::$_paths[$class];
    }

    public static function getAdminMenu()
    {
        return [];
    }

    /**
     * @return Model|null
     */
    public static function getSettingsModel()
    {
        return null;
    }

    public static function getSettingsForm()
    {
        return new ModelForm();
    }

    public function getSettings()
    {
        $model = $this->getSettingsModel();
        if (!$model) {
            return null;
        }
        $settings = null;
        if (Phact::app()->hasComponent('cache') && $this->settingsModelCache) {
            /** @var Cache $cache */
            $cache = Phact::app()->getComponent('cache');
            $settingsKey = self::class . '__' . $model->className();
            $settings = $cache->get($settingsKey, false);
            if ($settings === false) {
                $settings = $model->objects()->get();
                $cache->set($settingsKey, $settings, $this->settingsModelCache);
            }
        } else {
            $settings = $model->objects()->get();
        }
        if (!$settings) {
            $settings = $model;
        }
        return $settings;
    }

    public function getSetting($name)
    {
        $settings = $this->getSettings();
        return $settings->{$name};
    }
}