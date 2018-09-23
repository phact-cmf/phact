<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 23/09/2018 13:27
 */

namespace Phact\Components;

/**
 * Interface MetaInterface
 * @package Phact\Components
 */
interface MetaInterface
{
    /**
     * Setting title of current page
     * @param $title
     * @return mixed
     */
    public function setTitle($title);

    /**
     * Get title of current page
     * @return mixed
     */
    public function getTitle();

    /**
     * Set meta[description] of current page
     * @param $description
     * @return mixed
     */
    public function setDescription($description);

    /**
     * Get description of current
     * @return mixed
     */
    public function getDescription();

    /**
     * Set meta[keywords] of current page
     * @param $keywords
     * @return mixed
     */
    public function setKeywords($keywords);

    /**
     * Get keywords of current page
     * @return mixed
     */
    public function getKeywords();

    /**
     * Get canonical of current page
     * @return mixed
     */
    public function getCanonical();

    /**
     * Set link canonical of current page
     * @param $canonical
     * @return mixed
     */
    public function setCanonical($canonical);

    /**
     * Get all data
     * @return mixed
     */
    public function getData();

    /**
     * Set up with template
     * @param $key
     * @param array $params
     * @return mixed
     */
    public function useTemplate($key, $params = []);
}