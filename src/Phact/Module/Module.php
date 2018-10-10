<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 04/08/16 08:22
 */

namespace Phact\Module;

use Phact\Cache\Cache;
use Psr\SimpleCache\CacheInterface;
use Phact\Event\EventManagerInterface;
use Phact\Form\ModelForm;
use Phact\Helpers\ClassNames;
use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;
use Phact\Orm\Model;
use Phact\Translate\Translate;
use ReflectionClass;

/**
 * Class Module
 * @package Phact\Module
 */
abstract class Module
{
    use ClassNames, SmartProperties;

    protected static $_paths = [];

    protected $_name;

    public $settingsModelCache = 3600;

    /**
     * @var CacheInterface
     */
    protected $_cacheDriver;

    /**
     * @var Translate
     */
    protected $_translate;


    public function __construct(string $name, CacheInterface $cacheDriver = null, Translate $translate = null)
    {
        $this->_name = $name;
        $this->_cacheDriver = $cacheDriver;
        $this->_translate = $translate;
    }

    /**
     * Before application init
     */
    public function onApplicationInit()
    {
    }

    /**
     * Before application run
     */
    public function onApplicationRun()
    {
    }

    /**
     * Before application end
     */
    public function onApplicationEnd()
    {
    }

    /**
     * Module verbose (human-readable) name
     * @return mixed
     */
    public function getVerboseName()
    {
        return $this->getName();
    }

    /**
     * Module name
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Get module base path
     * @return mixed
     * @throws \ReflectionException
     */
    public function getPath()
    {
        $class = static::class;
        if (!isset(static::$_paths[$class])) {
            $rc = new ReflectionClass($class);
            static::$_paths[$class] = dirname($rc->getFileName());
        }
        return static::$_paths[$class];
    }

    /**
     * Settings model
     * @return Model|null
     */
    public function getSettingsModel()
    {
        return null;
    }

    /**
     * Settings form
     * @return ModelForm
     */
    public function getSettingsForm()
    {
        return new ModelForm();
    }

    /**
     * Settings model instance
     * @return null|Model
     */
    public function getSettings()
    {
        $model = $this->getSettingsModel();
        if (!$model) {
            return null;
        }
        $settings = null;
        if ($this->_cacheDriver && $this->settingsModelCache) {
            $settingsKey = $this->getSettingsCacheKey();
            $settings = $this->_cacheDriver->get($settingsKey, false);
            if ($settings === false) {
                $settings = $model->objects()->get();
                $this->_cacheDriver->set($settingsKey, $settings, $this->settingsModelCache);
            }
        } else {
            $settings = $model->objects()->get();
        }
        if (!$settings) {
            $settings = $model;
        }
        return $settings;
    }

    /**
     * Triggered after settings model update
     */
    public function afterSettingsUpdate()
    {
        if ($this->_cacheDriver && $this->settingsModelCache) {
            $model = $this->getSettingsModel();
            $settings = $model->objects()->get();
            $this->_cacheDriver->set($this->getSettingsCacheKey(), $settings, $this->settingsModelCache);
        }
    }

    /**
     * Settings cache key
     * @return string
     */
    protected function getSettingsCacheKey()
    {
        return static::class . '__' . $model->className();
    }

    /**
     * Get settings instance property
     * @param $name
     * @return bool|mixed|null
     */
    public function getSetting($name)
    {
        $settings = $this->getSettings();
        return $settings->{$name};
    }

    /**
     * Translate
     *
     * @param $domain
     * @param string $key
     * @param null $number
     * @param array $parameters
     * @param null $locale
     * @return string
     */
    public function t($domain, $key = "", $number = null, $parameters = [], $locale = null)
    {
        if ($this->_translate) {
            return $this->_translate->t($domain, $key, $number, $parameters, $locale);
        }
        return $key;
    }
}