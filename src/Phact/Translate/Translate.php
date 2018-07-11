<?php
/**
 * Created by PhpStorm.
 * User: anton
 * Date: 11/07/2018
 * Time: 16:01
 */

namespace Phact\Translate;


use Phact\Exceptions\InvalidConfigException;
use Phact\Helpers\Configurator;
use Phact\Helpers\SmartProperties;
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

    public function init()
    {
        $this->_locale = $this->detect();
        $this->_translator = new Translator($this->_locale);
        $this->initLoaders();
        $this->loadMessages();
    }

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

    public function initLoaders()
    {
        $this->_translator->addLoader('array', new ArrayLoader());
        $this->_translator->addLoader('php', new PhpFileLoader());
        $this->_translator->addLoader('mo', new MoFileLoader());
        $this->_translator->addLoader('po', new PoFileLoader());
        $this->_translator->addLoader('json', new JsonFileLoader());
    }

    public function loadMessages()
    {
        $this->loadSystemMessages();
    }

    public function loadSystemMessages()
    {
        $systemMessagesPath = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'messages']));
        if ($systemMessagesPath) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($systemMessagesPath,
                    \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $file) {
                /* @var \SplFileInfo $file */
                if ($file->isFile()) {
                    $locale = $file->getBasename('.' . $file->getExtension());
                    $domain = basename($file->getPath());
                    $filePath = $file->getPathname();

                    $this->_translator->addResource($file->getExtension(), $filePath, $locale, $domain);
                }
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
     * @throws InvalidConfigException
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
}