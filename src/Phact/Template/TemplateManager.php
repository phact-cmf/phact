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
 * @date 07/07/16 08:35
 */

namespace Phact\Template;


use Fenom;
use Fenom\Tag;
use Fenom\Tokenizer;
use Phact\Helpers\Paths;
use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class TemplateManager
{
    use SmartProperties;

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

    public function init()
    {
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
        $modulesPath = Paths::get('Modules');
        $activeModules = Phact::app()->getModulesList();
        $paths = [
            Paths::get('base') . DIRECTORY_SEPARATOR . $this->templateFolder
        ];
        foreach ($activeModules as $module) {
            $paths[] = implode(DIRECTORY_SEPARATOR, [$modulesPath, $module, $this->templateFolder]);
        }
        return $paths;
    }

    public function render($template, $params = [])
    {
        return $this->_renderer->fetch($template, $params);
    }

    public function extendRenderer()
    {
        $this->_renderer->addModifier('safe_element', function($variable, $param, $default = '') {
            return isset($variable[$param]) ? $variable[$param] : $default;
        });
        $this->_renderer->addModifier('not_in', function($variable, $array) {
            return !array_key_exists($variable, $array);
        });

        $this->_renderer->addAccessorSmart("app", "app", Fenom::ACCESSOR_PROPERTY);
        $this->_renderer->app = Phact::app();

        $this->_renderer->addAccessorSmart("request", 'Phact\Main\Phact::app()->request', Fenom::ACCESSOR_VAR);

        $this->_renderer->addAccessorSmart("user", 'Phact\Main\Phact::app()->user', Fenom::ACCESSOR_VAR);

        $this->_renderer->addAccessorSmart("setting", "Phact\\Main\\Phact::app()->settings->get", Fenom::ACCESSOR_CALL);

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

        if (Phact::app()->hasComponent('cache')) {
            $this->_renderer->addBlockFunction("__internal_cache", function ($params, $content) {
                if (count($params) == 2) {
                    Phact::app()->cache->set($params[0], $content, $params[1]);
                }
                return $content;
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
                        <?php if ($cacheResult = Phact\Main\Phact::app()->cache->get('. $params[0] .')) {
                            echo $cacheResult;
                        } else { ob_start(); ?> '. $body.'
                            <?php  $info = $tpl->getStorage()->getTag("__internal_cache");
                                echo call_user_func_array(
                                    $info["function"], array( array( "0" => '. $params[0] . ', "1" => '. $params[1].' ), 
                                    ob_get_clean(),  $tpl, &$var
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

    public function loadLibraries()
    {
        $extensions = null;
        $cacheKey = 'PHACT__TEMPLATE_EXTENSIONS';
        if ($this->librariesCacheTimeout) {
            $extensions = Phact::app()->cache->get($cacheKey);
        }
        if (is_null($extensions)) {
            $extensions = [];
            $modulesPath = Paths::get('Modules');
            $activeModules = Phact::app()->getModulesList();
            $classes = [];
            foreach ($activeModules as $module) {
                $path = implode(DIRECTORY_SEPARATOR, [$modulesPath, $module, $this->librariesFolder]);
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
            if ($this->librariesCacheTimeout) {
                Phact::app()->cache->set($cacheKey, $extensions, $this->librariesCacheTimeout);
            }
        }
        $renderer = $this->getRenderer();
        foreach ($extensions as $extension) {
            $this->addExtension($renderer, $extension['class'], $extension['method'], $extension['name'], $extension['kind']);
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
        }
    }
}