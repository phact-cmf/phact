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
 * @date 08/09/16 07:22
 */

namespace Phact\Template;

use Fenom;
use Phact\Helpers\Text;
use ReflectionClass;
use ReflectionMethod;

class TemplateLibrary
{
    public static $excludedMethods = [];
    public static $excludedMethodsInternal = ['load', 'getExtensionName', 'getPrefix', 'getModuleName', 'addExtension'];

    public static function load($renderer)
    {
        $reflection = new ReflectionClass(static::class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PUBLIC);
        $kinds = ['function', 'functionSmart', 'modifier', 'compiler', 'accessorProperty', 'accessorFunction'];

        static::$excludedMethods = array_merge(self::$excludedMethods, self::$excludedMethodsInternal);

        foreach ($methods as $method) {
            if (!in_array($method->name, static::$excludedMethods)) {
                $kind = null;
                $name = null;
                $doc = $method->getDocComment();
                if ($doc) {
                    if (preg_match('/\@kind\s+(.*?)(?:\s|\*)/', $doc, $rawKind)) {
                        $kind = $rawKind[1];
                    };

                    if (preg_match('/\@name\s+(.*?)(?:\s|\*)/', $doc, $rawName)) {
                        $name = $rawName[1];
                    };
                }
                if (!in_array($kind, $kinds)) {
                    $kind = $kinds[0];
                }
                if (!$name) {
                    $name = static::getExtensionName($method->name);
                }
                static::addExtension($renderer, $method->name, $name, $kind);
            }
        }
    }

    public static function getExtensionName($methodName = null)
    {
        $prefix = static::getPrefix();
        return $prefix . '_' . Text::camelCaseToUnderscores($methodName);
    }

    public static function getPrefix()
    {
        return Text::camelCaseToUnderscores(static::getModuleName());
    }

    public static function getModuleName()
    {
        $class = get_called_class();
        $classParts = explode('\\', $class);
        if ($classParts[0] == 'Modules' && isset($classParts[1])) {
            return $classParts[1];
        }
        return null;
    }

    /**
     * @param $renderer Fenom
     * @param $methodName
     * @param $name
     * @param $kind
     */
    public static function addExtension($renderer, $methodName, $name, $kind)
    {
        $callable = [static::class, $methodName];
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