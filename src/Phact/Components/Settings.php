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
 * @date 30/01/17 16:39
 */

namespace Phact\Components;


use Phact\Main\Phact;
use Phact\Module\Module;

class Settings
{
    public function get($name)
    {
        $info = explode('.', $name);
        if (count($info) == 2) {
            $moduleName = $info[0];
            $attributeName = $info[1];
            /** @var Module $module */
            $module = Phact::app()->getModule($moduleName);
            if ($module && $attributeName) {
                return $module->getSetting($attributeName);
            }
        }
        return null;
    }
}