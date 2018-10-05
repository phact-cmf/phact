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

use Exception;
use Phact\Application\ModulesInterface;
use Phact\Commands\CommandInterface;
use Phact\Helpers\SmartProperties;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

/**
 * Command-line interface request
 *
 * Class CliRequest
 * @package Phact\Request
 */
class CliRequest implements CliRequestInterface
{
    use SmartProperties;

    /**
     * @var ModulesInterface
     */
    protected $_modules;

    public function __construct(ModulesInterface $modules)
    {
        $this->_modules = $modules;
    }

    protected function getArgs()
    {
        $args = [];
        if (isset($_SERVER['argv'])) {
            $args = $_SERVER['argv'];
        }
        return $args;
    }

    /**
     * Check is empty request
     *
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->getArgs()) <= 1;
    }

    /**
     * @return array[]
     * @throws Exception
     */
    public function match()
    {
        list($module, $commandName, $action, $arguments) = $this->parse();
        $class = '\\Modules\\' . $module . '\\Commands\\' . $commandName . 'Command';
        if (!class_exists($class) || !is_a($class, CommandInterface::class, true)) {
            throw new Exception("There is no {$commandName} command in module {$module}");
        }
        return [$class, $action, $arguments];
    }

    /**
     * Retrieve module, command, action and arguments from cli request
     * @return array
     */
    protected function parse()
    {
        $module = null;
        $command = null;
        $action = 'handle';
        $arguments = [];

        $args = $this->getArgs();
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

        $module = ucfirst($module);
        $command = ucfirst($command);

        return [$module, $command, $action, $arguments];
    }

    /**
     * Get available commands list
     *
     * @return CommandInterface[]
     */
    public function getCommandsList()
    {
        $commands = [];
        foreach ($this->_modules->getModules() as $moduleName => $module) {
            $path = implode(DIRECTORY_SEPARATOR, [$module->getPath(), 'Commands']);
            if (is_dir($path)) {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename) {
                    if ($filename->isDir()) continue;
                    $name = $filename->getBasename('.php');
                    $class = implode('\\', [$module::classNamespace(), 'Commands', $name]);
                    try {
                        $reflection = new ReflectionClass($class);
                        if (!$reflection->isAbstract()) {
                            $commands[] = $class;
                        }
                    } catch (\ReflectionException $exception) {
                    }
                }
            }
        }
        return $commands;
    }
}