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
 * @date 10/04/16 08:19
 */

namespace Phact\Helpers;


class Paths
{
    protected static $_paths = [];

    public static function add($name, $path)
    {
        self::$_paths[$name] = rtrim($path, DIRECTORY_SEPARATOR);
    }

    public static function get($name)
    {
        if (isset(self::$_paths[$name])) {
            return self::$_paths[$name];
        } else {
            $explodedName = explode('.', $name);
            $tail = [];
            while (count($explodedName) > 0) {
                $tail[] = array_pop($explodedName);
                $namePart = implode('.', $explodedName);
                if (isset(self::$_paths[$namePart])) {
                    $tail = array_reverse($tail);
                    return self::$_paths[$namePart] . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $tail);
                }
            }
        }
        return null;
    }
}