<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 22/09/2018 17:40
 */

namespace Phact\Components;

/**
 * Breadcrumbs chains management
 *
 * Interface BreadcrumbsInterface
 * @package Phact\Components
 */
interface BreadcrumbsInterface
{
    /**
     * Set active breadcrumbs chain
     *
     * @param string $name
     * @return Breadcrumbs
     */
    public function setActive($name);

    /**
     * Get active breadcrumbs chain name
     *
     * @return string
     */
    public function getActive();

    /**
     * Fluent setter of active breadcrumbs chain
     *
     * @param string $name
     * @return $this
     */
    public function to($name);

    /**
     * Clear active breadcrumbs chain
     *
     * @return array
     */
    public function clear();

    /**
     * Add item to active breadcrumbs chain
     *
     * @param $name
     * @param null $url
     * @param array $params
     * @throws \Exception
     */
    public function add($name, $url = null, $params = []);

    /**
     * Get full breadcrumbs chain by name
     *
     * @param string $name
     * @return array|mixed
     */
    public function get($name);
}