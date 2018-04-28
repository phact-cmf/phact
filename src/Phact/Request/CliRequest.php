<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 02/08/16 12:31
 */

namespace Phact\Request;

use Phact\Commands\Command;
use Phact\Helpers\Paths;
use Phact\Main\Phact;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

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

    public function getCommandsList()
    {
        $modulesPath = Paths::get('Modules');
        $activeModules = Phact::app()->getModulesList();
        $data = [];
        foreach ($activeModules as $module) {
            $path = implode(DIRECTORY_SEPARATOR, [$modulesPath, $module, 'Commands']);
            if (is_dir($path)) {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename)
                {
                    if ($filename->isDir()) continue;
                    $name = $filename->getBasename('.php');
                    if (!isset($data[$module])) {
                        $data[$module] = [];
                    }
                    $class = implode('\\', ['Modules', $module, 'Commands', $name]);
                    $reflection = new ReflectionClass($class);
                    if (!$reflection->isAbstract()) {
                        /** @var Command $command */
                        $command = new $class();
                        $name = preg_replace('/Command$/', '', $name);
                        $data[$module][$name] = $command->getDescription();
                    }
                }
            }
        }
        return $data;
    }
}