<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 22/09/2018 14:25
 */

namespace Phact\Components;

/**
 * Contains information about system paths
 *
 * Interface PathInterface
 * @package Phact\Components
 */
interface PathInterface
{
    /**
     * Add file to known paths
     *
     * @param $name
     * @param $path
     */
    public function add(string $name, string $path);

    /**
     * Get path by alias
     *
     * @param $name
     * @return null|string
     */
    public function get(string $name);

    /**
     * Retrieve file path by alias and array of allowed extensions
     *
     * @param string $name
     * @param array $extensions
     * @return null|string
     */
    public function file(string $name, $extensions = []);
}