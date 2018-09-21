<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 07/07/16 08:35
 */

namespace Phact\Template;


use Fenom;
use Fenom\Tag;
use Fenom\Tokenizer;
use Phact\Application\Application;
use Phact\Cache\CacheDriverInterface;
use Phact\Event\EventManagerInterface;
use Phact\Event\Events;
use Phact\Helpers\Paths;
use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;
use Phact\Router\Router;
use Phact\Translate\Translate;
use Phact\Translate\Translator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class TemplateManager
{
    use SmartProperties, Events, Translator;

    /**
     * @var Fenom
     */
    protected $_renderer;

    public $forceCompile = false;
    public $autoReload = true;
    public $autoEscape = true;

    public $templateFolder = 'templates';
    public $librariesFolder = 'TemplateLibraries';
    public $cacheFolder = 'templates_cache';

    public $librariesCacheTimeout;

    /**
     * @var CacheDriverInterface|null
     */
    protected $_cacheDriver;

    /**
     * @var Application
     */
    protected $_application;

    /**
     * @var EventManagerInterface
     */
    protected $_eventManager;

    public function __construct(EventManagerInterface $eventManager, Application $application, CacheDriverInterface $cacheDriver = null)
    {
        $this->_cacheDriver = $cacheDriver;
        $this->_application = $application;

        $paths = $this->collectTemplatesPaths();
        $provider = new PhactFenomTemplateProvider($paths);
        $cacheFolder = Paths::get('runtime.' . $this->cacheFolder);
        if (!is_dir($cacheFolder)) {
            mkdir($cacheFolder, 0777, true);
        }
        $this->_renderer = new Fenom($provider);
        $this->_renderer->setCompileDir($cacheFolder);
        $this->_renderer->setOptions([
            'force_compile' => $this->forceCompile,
            'auto_reload' => $this->autoReload,
            'auto_escape' => $this->autoEscape
        ]);
        $this->extendRenderer();
        $this->loadLibraries();
    }

    /**
     * @extension modifier
     * @name info
     * @return Fenom
     */
    public function getRenderer()
    {
        return $this->_renderer;
    }

    /**
     * @return array Paths of templates
     */
    protected function collectTemplatesPaths()
    {
        $paths = [];
        if ($this->_application) {
            $paths = [
                $this->_application->getBasePath() . DIRECTORY_SEPARATOR . $this->templateFolder
            ];
            $activeModules = $this->_application->getModulesConfig();
            foreach ($activeModules as $module => $config) {
                $moduleClass = $config['class'];
                $paths[] = implode(DIRECTORY_SEPARATOR, [$moduleClass::getPath(), $this->templateFolder]);
            }
        }
        return $paths;
    }

    public function render($template, $params = [])
    {
        $this->eventTrigger('template.beforeRender', [$template, $params], $this);
        $result = $this->_renderer->fetch($template, $params);
        $this->eventTrigger('template.afterRender', [$template, $params, $result], $this);
        return $result;
    }

    public function extendRenderer()
    {
        $this->_renderer->addModifier('safe_element', function($variable, $param, $default = '') {
            return isset($variable[$param]) ? $variable[$param] : $default;
        });
        $this->_renderer->addModifier('not_in', function($variable, $array) {
            return !array_key_exists($variable, $array);
        });

        if ($this->_application) {
            $this->_renderer->addAccessorSmart("app", "app", Fenom::ACCESSOR_PROPERTY);
            $this->_renderer->app = Phact::app();

            $this->_renderer->addAccessorSmart("user", 'Phact\Main\Phact::app()->user', Fenom::ACCESSOR_VAR);

            if (Phact::app()->hasComponent('request')) {
                $this->_renderer->addAccessorSmart("request", 'Phact\Main\Phact::app()->request', Fenom::ACCESSOR_VAR);
            }

            if (Phact::app()->hasComponent('settings')) {
                $this->_renderer->addAccessorSmart("setting", "Phact\\Main\\Phact::app()->settings->get", Fenom::ACCESSOR_CALL);
            }

            if ($this->_application->hasComponent(Router::class)) {
                $this->_renderer->addAccessorSmart("url", function($routeName, $params = array()) {
                    return $this->_application->getComponent(Router::class)->url($routeName, $params);
                }, Fenom::ACCESSOR_CALL);
            }

            if (Phact::app()->hasComponent('translate', Translate::class)) {
                $this->_renderer->addAccessorSmart("t", "Phact\\Main\\Phact::app()->translate->t", Fenom::ACCESSOR_CALL);
            }
        }

        $this->_renderer->addModifier('class', function($object) {
            if (is_object($object)) {
                return get_class($object);
            }
            return null;
        });

        $this->_renderer->addFunction('url', function($params) {
            $route = isset($params['route']) ? $params['route'] : null;
            if (!$route) {
                $route = isset($params[0]) ? $params[0] : null;
            }

            $attributes = isset($params['params']) ? $params['params'] : [];
            if (!$attributes) {
                $attributes = isset($params[1]) ? $params[1] : [];
            }
            return Phact::app()->router->url($route, $attributes);
        });

        $this->_renderer->addFunction('t', function($params) {
            return forward_static_call_array([self::class, 't'], $params);
        });

        if ($this->_cacheDriver) {
            $this->_renderer->addBlockFunction("__internal_cache_set", function ($params, $content) {
                if (count($params) == 2) {
                    $this->_cacheDriver->set($params[0], $content, $params[1]);
                }
                return $content;
            });

            $this->_renderer->addBlockFunction("__internal_cache_get", function ($params, $content) {
                if (count($params) == 1 && $this->_cacheDriver) {
                    return $this->_cacheDriver->get($params[0]);
                }
                return "";
            });

            $this->_renderer->addBlockCompiler("cache", function ($tokenizer, Tag $tag) {
                $params = $tag->tpl->parseParams($tokenizer);
                if (count($params) == 2) {
                    $tag['params'] = $params;
                } else {
                    $tag['params'] = null;
                }
                return '';
            }, function ($tokenizer, Tag $tag) {
                $body = $tag->getContent();
                $params = $tag['params'];
                if ($params) {
                    $result = '
                        <?php
                        $info = $tpl->getStorage()->getTag("__internal_cache_get");
                        $value = call_user_func_array(
                            $info["function"], 
                            array(
                                array( "0" => '. $params[0] . '), "",  $tpl, &$var
                            )
                        );
                        if ($value) {
                            echo $value;
                        } else { ob_start(); ?> '. $body.'
                            <?php $info = $tpl->getStorage()->getTag("__internal_cache_set");
                                echo call_user_func_array(
                                    $info["function"], 
                                    array( 
                                        array( "0" => '. $params[0] . ', "1" => '. $params[1].' ), 
                                        ob_get_clean(),  
                                        $tpl, 
                                        &$var
                                    )
                                ); ?>
                        <?php } ?>
                    ';
                    $tag->replaceContent($result);
                }
                return;
            });
        }
    }

    public function getCacheDriver()
    {
        return $this->_cacheDriver;
    }

    public function loadLibraries()
    {
        $extensions = null;
        $cacheKey = 'PHACT__TEMPLATE_EXTENSIONS';
        if ($this->librariesCacheTimeout && $this->_cacheDriver) {
            $extensions = $this->_cacheDriver->get($cacheKey);
        }
        if (is_null($extensions) && $this->_application) {
            $activeModules = $this->_application->getModulesConfig();
            $classes = [];
            foreach ($activeModules as $module => $config) {
                $moduleClass = $config['class'];
                $path = implode(DIRECTORY_SEPARATOR, [$moduleClass::getPath(), $this->librariesFolder]);
                if (is_dir($path)) {
                    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename) {
                        // filter out "." and ".."
                        if ($filename->isDir()) continue;
                        $name = $filename->getBasename('.php');
                        $classes[] = implode('\\', ['Modules', $module, $this->librariesFolder, $name]);
                    }
                }
            }
            foreach ($classes as $class) {
                if (class_exists($class) && is_a($class, TemplateLibrary::class, true)) {
                    $extensions = array_merge($extensions, $class::getExtensions());
                }
            }
            if ($this->librariesCacheTimeout && $this->_cacheDriver) {
                $this->_cacheDriver->set($cacheKey, $extensions, $this->librariesCacheTimeout);
            }
        }
        $renderer = $this->getRenderer();
        if (is_array($extensions)) {
            foreach ($extensions as $extension) {
                $this->addExtension($renderer, $extension['class'], $extension['method'], $extension['name'], $extension['kind']);
            }
        }
    }

    /**
     * @param $renderer Fenom
     * @param $methodName
     * @param $name
     * @param $kind
     */
    public function addExtension($renderer, $class, $methodName, $name, $kind)
    {
        $callable = [$class, $methodName];
        switch ($kind) {
            case 'function':
                $renderer->addFunction($name, $callable);
                break;
            case 'functionSmart':
                $renderer->addFunctionSmart($name, $callable);
                break;
            case 'modifier':
                $renderer->addModifier($name, $callable);
                break;
            case 'compiler':
                $renderer->addCompiler($name, $callable);
                break;
            case 'accessorProperty':
                $renderer->addAccessorCallback($name, $callable);
                break;
            case 'accessorFunction':
                $renderer->addAccessorSmart($name, implode('::', $callable), $renderer::ACCESSOR_CALL);
                break;
            case 'blockFunction':
                $renderer->addBlockFunction($name, $callable);
                break;
            case 'blockCompiler':
                $renderer->addBlockCompiler($name, $callable);
                break;
        }
    }
}