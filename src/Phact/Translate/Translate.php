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


use Phact\Application\ModulesInterface;
use Phact\Components\PathInterface;
use Phact\Event\EventManagerInterface;
use Phact\Helpers\SmartProperties;
use Phact\Module\Module;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\JsonFileLoader;
use Symfony\Component\Translation\Loader\MoFileLoader;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Loader\PoFileLoader;
use Symfony\Component\Translation\Translator as SymfonyTranslator;
use Symfony\Component\Translation\TranslatorInterface;

class Translate
{
    use SmartProperties;

    /** @var TranslatorInterface */
    protected $_translator;

    /**
     * @var array
     */
    protected $_locales = [];

    /** @var string */
    protected $_locale;

    /**
     * @var LocaleDetector|callable|null
     */
    protected $_localeDetector;

    /**
     * @var PathInterface
     */
    protected $_path;

    /***
     * @var ModulesInterface
     */
    protected $_modules;

    /***
     * @var EventManagerInterface|null
     */
    protected $_eventManager;

    public function __construct($localeDetector = null, PathInterface $path = null, TranslatorInterface $translator = null, ModulesInterface $modules = null, EventManagerInterface $eventManager = null)
    {
        $this->_localeDetector = $localeDetector;
        $this->_locale = $this->detect();
        $this->_modules = $modules;
        $this->_path = $path;
        $this->_eventManager = $eventManager;

        $this->_translator = $translator ?: new SymfonyTranslator($this->_locale);

        $this->initLoaders();
        $this->loadMessages();
        $this->subscribe();
    }

    /**
     * Subscribe on module init
     */
    protected function subscribe()
    {
        if ($this->_eventManager) {
            $this->_eventManager->on('module.afterInit', function ($module) {
                $this->loadModuleMessages($module);
            });
            $this->_eventManager->on('application.afterModulesInit', function () {
                $this->loadApplicationMessages();
            });
        }
    }

    /**
     * @return TranslatorInterface
     */
    public function getTranslator()
    {
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
        return $locale ?: 'en';
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
        if ($this->_modules) {
            foreach ($this->_modules->getModules() as $moduleName => $module) {
                $this->loadModuleMessages($module);
            }
        }
    }

    /**
     * Load module-located resources
     * @param $module Module
     */
    public function loadModuleMessages($module)
    {
        $modulePath = $module->getPath();
        $moduleMessagesPath = realpath(implode(DIRECTORY_SEPARATOR, [$modulePath, 'messages']));
        if ($moduleMessagesPath) {
            $this->loadFileSystemMessages($moduleMessagesPath, "{$module->getName()}.");
        }
    }

    /**
     * Load app-located resources
     */
    public function loadApplicationMessages()
    {
        if ($this->_path && $this->_path->get('base.messages')) {
            $systemMessagesPath = realpath($this->_path->get('base.messages'));
            if ($systemMessagesPath) {
                $this->loadFileSystemMessages($systemMessagesPath, "");
            }
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
     * @return string
     */
    public function getLocale()
    {
        return $this->_locale;
    }

    /**
     * @param $locale
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
        if (mb_strpos($domain, ".", 0, "UTF-8") === false && $this->_modules && in_array($domain, $this->_modules->getModulesList())) {
            $domain .= ".messages";
        }
        if ((mb_strpos($key, "|", 0, "UTF-8") !== false) && $number) {
            return $this->getTranslator()->transChoice($key, $number, $parameters, $domain, $locale);
        } else {
            return $this->getTranslator()->trans($key, $parameters, $domain, $locale);
        }
    }
}