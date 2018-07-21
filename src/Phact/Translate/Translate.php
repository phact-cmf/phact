<?php

/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 11/07/2018 16:01
 */

namespace Phact\Translate;


use Phact\Exceptions\InvalidConfigException;
use Phact\Helpers\Configurator;
use Phact\Helpers\Paths;
use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\JsonFileLoader;
use Symfony\Component\Translation\Loader\MoFileLoader;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Loader\PoFileLoader;
use Symfony\Component\Translation\Translator;

class Translate
{
    use SmartProperties;

    /** @var Translator */
    protected $_translator;

    protected $_locales = [];

    /** @var string */
    protected $_locale;

    /**
     * @var LocaleDetector|callable|null
     */
    protected $_localeDetector;

    /** @var string */
    public $default = 'en';

    /**
     * Initialization
     */
    public function init()
    {
        $this->_locale = $this->detect();
        $this->_translator = new Translator($this->_locale);
        $this->initLoaders();
        $this->loadMessages();
    }

    /**
     * @return Translator
     */
    public function getTranslator()
    {
        if (!$this->_translator) {
            $this->init();
        }
        return $this->_translator;
    }

    /**
     * Detect current locale
     *
     * @return string
     */
    public function detect()
    {
        $locale = null;
        if ($this->_localeDetector) {
            if ($this->_localeDetector instanceof LocaleDetector) {
                $locale = $this->_localeDetector;
            } else {
                $locale = call_user_func($this->_localeDetector);
            }
        }
        return $locale ?: $this->default;
    }

    /**
     * Init translator loaders
     */
    public function initLoaders()
    {
        $this->_translator->addLoader('array', new ArrayLoader());
        $this->_translator->addLoader('php', new PhpFileLoader());
        $this->_translator->addLoader('mo', new MoFileLoader());
        $this->_translator->addLoader('po', new PoFileLoader());
        $this->_translator->addLoader('json', new JsonFileLoader());
    }

    /**
     * Load messages
     */
    public function loadMessages()
    {
        $this->loadSystemMessages();
        $this->loadModulesMessages();
        $this->loadApplicationMessages();
    }

    /**
     * Load system-located resources
     */
    public function loadSystemMessages()
    {
        $systemMessagesPath = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'messages']));
        if ($systemMessagesPath) {
            $this->loadFileSystemMessages($systemMessagesPath, "Phact.");
        }
    }

    /**
     * Load modules-located resources
     */
    public function loadModulesMessages()
    {
        foreach (Phact::app()->getModulesConfig() as $moduleName => $config) {
            $moduleClass = $config['class'];
            $modulePath = $moduleClass::getPath();
            $moduleMessagesPath = realpath(implode(DIRECTORY_SEPARATOR, [$modulePath, 'messages']));
            if ($moduleMessagesPath) {
                $this->loadFileSystemMessages($moduleMessagesPath, "{$moduleName}.");
            }
        }
    }

    /**
     * Load app-located resources
     */
    public function loadApplicationMessages()
    {
        $systemMessagesPath = realpath(Paths::get('base.messages'));
        if ($systemMessagesPath) {
            $this->loadFileSystemMessages($systemMessagesPath, "");
        }
    }

    public function loadFileSystemMessages($path, $domainPrefix = "")
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path,
                \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            /* @var \SplFileInfo $file */
            if ($file->isFile()) {
                $locale = $file->getBasename('.' . $file->getExtension());
                $domain = basename($file->getPath());
                $filePath = $file->getPathname();

                $this->_translator->addResource($file->getExtension(), $filePath, $locale, "{$domainPrefix}{$domain}");
            }
        }
    }

    /**
     * @return array
     */
    public function getLocales()
    {
        return $this->_locales;
    }

    /**
     * @param array $locales
     */
    public function setLocales($locales)
    {
        $this->_locales = $locales;
    }

    /**
     * @param callable|null|LocaleDetector $localeDetector
     * @return Translate
     * @throws InvalidConfigException If locale detector is not callable and not instance of LocaleDetector
     */
    public function setLocaleDetector($localeDetector)
    {
        if (is_array($localeDetector)) {
            $this->_localeDetector = Configurator::create($localeDetector);
            if (!($this->_localeDetector instanceof LocaleDetector)) {
                throw new InvalidConfigException("Locale detector must be instance of \\Phact\\Translate\\LocaleDetector");
            }
        } elseif (is_callable($localeDetector)) {
            $this->_localeDetector = $localeDetector;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->_locale;
    }

    /**
     * @return string
     */
    public function setLocale($locale)
    {
        $this->getTranslator()->setLocale($locale);
        $this->_locale = $locale;
    }

    /**
     * @param $key
     * @param string $domain
     * @param int|null $number
     * @param null|array $parameters
     * @param null|string $locale
     * @return string
     */
    public function t($domain, $key = "", $number = null, $parameters = [], $locale = null)
    {
        if (!$key) {
            $key = $domain;
            $domain = "";
        }
        if (is_string($parameters)) {
            $locale = $parameters;
        }
        if (is_array($number)) {
            $parameters = $number;
        }
        if (mb_strpos($domain, ".", 0, "UTF-8") === false && in_array($domain, Phact::app()->getModulesList())) {
            $domain .= ".messages";
        }
        if ((mb_strpos($key, "|", 0, "UTF-8") !== false) && $number) {
            return $this->getTranslator()->transChoice($key, $number, $parameters, $domain, $locale);
        } else {
            return $this->getTranslator()->trans($key, $parameters, $domain, $locale);
        }
    }
}