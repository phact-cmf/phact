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
use Phact\Application\ModulesInterface;
use Phact\Cache\CacheDriverInterface;
use Phact\Components\PathInterface;
use Phact\Components\Settings;
use Phact\Event\EventManagerInterface;
use Phact\Event\Events;
use Phact\Helpers\SmartProperties;
use Phact\Interfaces\AuthInterface;
use Phact\Request\HttpRequest;
use Phact\Router\Router;
use Phact\Translate\Translate;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class TemplateManager implements RendererInterface
{
    use SmartProperties, Events;

    /**
     * @var ExtendedFenom
     */
    protected $_renderer;

    /**
     * Fenom option
     * @var bool
     */
    public $forceCompile = false;

    /**
     * Fenom option
     * @var bool
     */
    public $autoReload = true;

    /**
     * Fenom option
     * @var bool
     */
    public $autoEscape = true;

    /**
     * Application template folder
     *
     * @var string
     */
    public $templateFolder = 'templates';

    /**
     * Libraries folder in module
     *
     * @var string
     */
    public $librariesFolder = 'TemplateLibraries';

    /**
     * Cache folder in runtime
     *
     * @var string
     */
    public $cacheFolder = 'templates_cache';

    /**
     * Timeout for cache libraries from modules
     *
     * @var int
     */
    public $librariesCacheTimeout;

    /**
     * @var CacheDriverInterface|null
     */
    protected $_cacheDriver;

    /**
     * @var ModulesInterface
     */
    protected $_modules;

    /**
     * @var PathInterface
     */
    protected $_paths;

    /**
     * @var AuthInterface
     */
    protected $_auth;

    /**
     * @var HttpRequest
     */
    protected $_request;

    /**
     * @var Settings
     */
    protected $_settings;

    /**
     * @var Router
     */
    protected $_router;

    /**
     * @var Translate
     */
    protected $_translate;

    public function __construct(
        EventManagerInterface $eventManager = null,
        ModulesInterface $modules = null,
        CacheDriverInterface $cacheDriver = null,
        PathInterface $paths = null,
        AuthInterface $auth = null,
        HttpRequest $request = null,
        Settings $settings = null,
        Router $router = null,
        Translate $translate = null)
    {
        $this->_eventManager = $eventManager;
        $this->_cacheDriver = $cacheDriver;
        $this->_modules = $modules;
        $this->_paths = $paths;
        $this->_auth = $auth;
        $this->_request = $request;
        $this->_settings = $settings;
        $this->_router = $router;
        $this->_translate = $translate;
    }

    /**
     * @extension modifier
     * @return ExtendedFenom
     */
    public function getRenderer()
    {
        if (!$this->_renderer) {
            $this->initRenderer();
        }
        return $this->_renderer;
    }

    /**
     * Initializing renderer
     */
    public function initRenderer()
    {
        $paths = $this->collectTemplatesPaths();
        $provider = new PhactFenomTemplateProvider($paths);
        $this->_renderer = new ExtendedFenom($provider);

        if ($this->_paths) {
            $cacheFolder = $this->_paths->get('runtime.' . $this->cacheFolder);
            if (!is_dir($cacheFolder)) {
                mkdir($cacheFolder, 0777, true);
            }
            $this->_renderer->setCompileDir($cacheFolder);
        }

        $this->_renderer->setOptions([
            'force_compile' => $this->forceCompile,
            'auto_reload' => $this->autoReload,
            'auto_escape' => $this->autoEscape
        ]);

        $this->extendRenderer();
        $this->loadLibraries();
    }

    /**
     * @return array Paths of templates
     */
    protected function collectTemplatesPaths()
    {
        $paths = [];
        if ($this->_modules && $this->_paths) {
            $paths = [
                $this->_paths->get('base') . DIRECTORY_SEPARATOR . $this->templateFolder
            ];
            $activeModules = $this->_modules->getModules();
            foreach ($activeModules as $name => $module) {
                $paths[] = implode(DIRECTORY_SEPARATOR, [$module->getPath(), $this->templateFolder]);
            }
        }
        return $paths;
    }

    /**
     * Render template with renderer
     *
     * @param $template
     * @param array $params
     * @return mixed
     */
    public function render($template, $params = [])
    {
        $this->eventTrigger('template.beforeRender', [$template, $params], $this);
        $result = $this->getRenderer()->fetch($template, $params);
        $this->eventTrigger('template.afterRender', [$template, $params, $result], $this);
        return $result;
    }

    /**
     * Add some improvements
     */
    public function extendRenderer()
    {
        $this->getRenderer()->addModifier('safe_element', function($variable, $param, $default = '') {
            return isset($variable[$param]) ? $variable[$param] : $default;
        });

        $this->getRenderer()->addModifier('not_in', function($variable, $array) {
            return !array_key_exists($variable, $array);
        });

        $this->getRenderer()->addModifier('class', function($object) {
            if (is_object($object)) {
                return get_class($object);
            }
            return null;
        });

        if ($this->_auth) {
            $this->getRenderer()->addAccessorProp("user", function () {
                return $this->_auth->getUser();
            });
        }

        if ($this->_request) {
            $this->getRenderer()->addAccessorProp("request", function () {
                return $this->_request;
            });
        }

        if ($this->_settings) {
            $this->getRenderer()->addAccessorCallable("setting", function ($name) {
                return $this->_settings->get($name);
            });
        }

        if ($this->_router) {
            $this->getRenderer()->addAccessorCallable('url', function($routeName, $params = array()) {
                return $this->_router->url($routeName, $params);
            });

            $this->getRenderer()->addFunction('url', function($params) {
                $route = isset($params['route']) ? $params['route'] : null;
                if (!$route) {
                    $route = isset($params[0]) ? $params[0] : null;
                }

                $attributes = isset($params['params']) ? $params['params'] : [];
                if (!$attributes) {
                    $attributes = isset($params[1]) ? $params[1] : [];
                }
                return $this->_router->url($route, $attributes);
            });
        }

        if ($this->_translate) {
            $this->getRenderer()->addAccessorCallable("t", function ($domain, $key = "", $number = null, $parameters = [], $locale = null) {
                return $this->_translate->t($domain, $key, $number, $parameters, $locale);
            });

            $this->getRenderer()->addFunction('t', function($params) {
                return call_user_func_array([$this->_translate, 't'], $params);
            });
        }

        if ($this->_cacheDriver) {
            $this->getRenderer()->addBlockFunction("__internal_cache_set", function ($params, $content) {
                if (count($params) == 2) {
                    $this->_cacheDriver->set($params[0], $content, $params[1]);
                }
                return $content;
            });

            $this->getRenderer()->addBlockFunction("__internal_cache_get", function ($params, $content) {
                if (count($params) == 1 && $this->_cacheDriver) {
                    return $this->_cacheDriver->get($params[0]);
                }
                return "";
            });

            $this->getRenderer()->addBlockCompiler("cache", function ($tokenizer, Tag $tag) {
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

    /**
     * Load external libraries from modules
     */
    public function loadLibraries()
    {
        $extensions = null;
        $cacheKey = 'PHACT__TEMPLATE_EXTENSIONS';
        if ($this->librariesCacheTimeout && $this->_cacheDriver) {
            $extensions = $this->_cacheDriver->get($cacheKey);
        }
        if (is_null($extensions) && $this->_modules) {
            $activeModules = $this->_modules->getModules();
            $classes = [];
            $extensions = [];
            foreach ($activeModules as $moduleName => $module) {
                $path = implode(DIRECTORY_SEPARATOR, [$module->getPath(), $this->librariesFolder]);
                if (is_dir($path)) {
                    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename) {
                        // filter out "." and ".."
                        if ($filename->isDir()) continue;
                        $name = $filename->getBasename('.php');
                        $classes[] = implode('\\', [$module::classNamespace(), $this->librariesFolder, $name]);
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
        $this->getRenderer();
        if (is_array($extensions)) {
            foreach ($extensions as $extension) {
                $this->addExtension($extension['class'], $extension['method'], $extension['name'], $extension['kind']);
            }
        }
    }

    /**
     * @param $methodName
     * @param $name
     * @param $kind
     */
    public function addExtension($class, $methodName, $name, $kind)
    {
        $renderer = $this->getRenderer();
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
                $renderer->addAccessorCallable($name, $callable);
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