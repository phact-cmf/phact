<?php

/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 10/04/16 08:19
 */

namespace Phact\Components;

use Phact\Helpers\SmartProperties;

/**
 * Contains information about system paths
 *
 * Class Path
 * @package Phact\Helpers
 */
class Path
{
    use SmartProperties;

    /**
     * Alias - path mappings
     *
     * @var array
     */
    protected $_paths = [];

    /**
     * Add file to known paths
     *
     * @param $name
     * @param $path
     */
    public function add(string $name, string $path)
    {
        $this->_paths[$name] = rtrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Add multiple elements to mappings
     *
     * @param $config
     */
    public function setPaths($config)
    {
        foreach ($config as $name => $path) {
            $this->add($name, $path);
        }
    }

    /**
     * Get path by alias
     *
     * @param $name
     * @return null|string
     */
    public function get(string $name)
    {
        if (isset($this->_paths[$name])) {
            return $this->_paths[$name];
        } else {
            $explodedName = explode('.', $name);
            $tail = [];
            while (count($explodedName) > 0) {
                $tail[] = array_pop($explodedName);
                $namePart = implode('.', $explodedName);
                if (isset($this->_paths[$namePart])) {
                    $tail = array_reverse($tail);
                    return $this->_paths[$namePart] . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $tail);
                }
            }
        }
        return null;
    }

    /**
     * Retrieve file path by alias and array of allowed extensions
     *
     * @param string $name
     * @param array $extensions
     * @return null|string
     */
    public function file(string $name, $extensions = [])
    {
        $path = $this->get($name);
        if (is_file($path)) {
            return $path;
        }
        if (!is_array($extensions)) {
            $extensions = [$extensions];
        }
        foreach ($extensions as $extension) {
            $fileName = $path . '.' . $extension;
            if (is_file($fileName)) {
                return $fileName;
            }
        }
        return null;
    }
}