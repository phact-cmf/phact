<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
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
    public static $excludedMethodsInternal = ['getExtensions', 'getExtensionName', 'getPrefix', 'getModuleName', 'addExtension'];

    public static function getExtensions()
    {
        $extensions = [];
        $class = static::class;
        $reflection = new ReflectionClass($class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PUBLIC);
        $kinds = ['function', 'functionSmart', 'blockFunction', 'modifier', 'compiler', 'accessorProperty', 'accessorFunction', 'blockCompiler'];

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
                $extensions[] = [
                    'method' => $method->name,
                    'name' => $name,
                    'kind' => $kind,
                    'class' => $class
                ];
            }
        }
        return $extensions;
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
}