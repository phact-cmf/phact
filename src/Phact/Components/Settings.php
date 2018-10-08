<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 30/01/17 16:39
 */

namespace Phact\Components;

use Phact\Application\ModulesInterface;

/**
 * Settings of modules component
 *
 * Class Settings
 * @package Phact\Components
 */
class Settings
{
    /** @var ModulesInterface */
    protected $_modules;

    public function __construct(ModulesInterface $modules)
    {
        $this->_modules = $modules;
    }

    public function get($name)
    {
        $info = explode('.', $name);
        if (count($info) == 2) {
            $moduleName = $info[0];
            $attributeName = $info[1];
            $module = $this->_modules->getModule($moduleName);
            if ($module && $attributeName) {
                return $module->getSetting($attributeName);
            }
        }
        return null;
    }
}