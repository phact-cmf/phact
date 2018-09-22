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

use Phact\Application\Application;
use Phact\Commands\Command;
use Phact\Helpers\SmartProperties;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

class CliRequest
{
    use SmartProperties;

    /**
     * @var Application
     */
    protected $_application;

    public function __construct(Application $application)
    {
        $this->_application = $application;
    }

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
        $activeModules = $this->_application->getModulesConfig();
        $data = [];
        foreach ($activeModules as $moduleName => $module) {
            $moduleClass = $module['class'];
            $path = implode(DIRECTORY_SEPARATOR, [$moduleClass::getPath(), 'Commands']);
            if (is_dir($path)) {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename)
                {
                    if ($filename->isDir()) continue;
                    $name = $filename->getBasename('.php');
                    if (!isset($data[$moduleName])) {
                        $data[$moduleName] = [];
                    }
                    $class = implode('\\', ['Modules', $moduleName, 'Commands', $name]);
                    $reflection = new ReflectionClass($class);
                    if (!$reflection->isAbstract()) {
                        /** @var Command $command */
                        $command = new $class();
                        $name = preg_replace('/Command$/', '', $name);
                        $data[$moduleName][$name] = $command->getDescription();
                    }
                }
            }
        }
        return $data;
    }
}