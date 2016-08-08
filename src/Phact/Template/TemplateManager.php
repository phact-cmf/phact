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
use Phact\Helpers\Paths;
use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;

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
    public $cacheFolder = 'templates_cache';

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
    }

    /**
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

        $this->_renderer->addModifier('class', function($object) {
            if (is_object($object)) {
                return get_class($object);
            }
            return null;
        });

        $this->_renderer->addFunction('url', function($params) {
            return Phact::app()->router->url($params['route'], isset($params['params']) ? $params['params'] : [] );
        });
    }
}