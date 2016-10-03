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
 * @date 02/08/16 12:31
 */

namespace Phact\Request;

use Phact\Helpers\SmartProperties;

class CliRequest extends Request
{
    public function parse()
    {
        $args = [];
        if (isset($_SERVER['argv'])) {
            $args = $_SERVER['argv'];
        }

        $module = null;
        $command = null;
        $action = 'handle';
        $arguments = [];

        foreach ($args as $key => $arg) {
            if ($key == 1) {
                $module = $arg;
            } elseif ($key == 2) {
                $command = $arg;
            } elseif ($key == 3) {
                $action = $arg;
            }  elseif ($key > 3) {
                $arguments[] = $arg;
            }
        }

        return [$module, $command, $action, $arguments];
    }
}