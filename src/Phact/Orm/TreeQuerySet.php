<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 16/01/17 15:43
 */

namespace Phact\Orm;


class TreeQuerySet extends QuerySet
{
    /**
     * Get descendants
     *
     * @param bool $includeSelf
     * @param null $depth
     * @return $this
     */
    public function descendants($includeSelf = false, $depth = null)
    {
        $this->filter([
            'lft__gte' => $this->getModel()->getAttribute('lft'),
            'rgt__lte' => $this->getModel()->getAttribute('rgt'),
            'root' => $this->getModel()->getAttribute('root')
        ])->order(['lft']);

        if ($includeSelf === false) {
            $this->exclude([
                'pk' => $this->getModel()->pk
            ]);
        }

        if (!is_null($depth)) {
            $this->filter([
                'depth__lte' => $this->getModel()->getAttribute('depth') + $depth
            ]);
        }

        return $this;
    }

    /**
     * Get children
     *
     * @param bool $includeSelf
     * @return $this
     */
    public function children($includeSelf = false)
    {
        return $this->descendants($includeSelf, 1);
    }

    /**
     * Get ancestors
     *
     * @param bool $includeSelf
     * @param null $depth
     * @return $this
     */
    public function ancestors($includeSelf = false, $depth = null)
    {
        $qs = $this->filter([
            'lft__lte' => $this->getModel()->getAttribute('lft'),
            'rgt__gte' => $this->getModel()->getAttribute('rgt'),
            'root' => $this->getModel()->getAttribute('root')
        ])->order(['-lft']);

        if ($includeSelf === false) {
            $this->exclude([
                'pk' => $this->getModel()->pk
            ]);
        }

        if (!is_null($depth)) {
            $qs = $qs->filter(['depth__lte' => $this->getModel()->getAttribute('depth') - $depth]);
        }

        return $qs;
    }

    /**
     * Get parents
     *
     * @param bool $includeSelf
     * @return $this
     */
    public function parents($includeSelf = false)
    {
        return $this->ancestors($includeSelf, 1);
    }

    /**
     * @return $this
     */
    public function roots()
    {
        return $this->filter(['lft' => 1]);
    }

    public function parent()
    {
        return $this->filter([
            'lft__lt' => $this->getModel()->getAttribute('lft'),
            'rgt__gt' => $this->getModel()->getAttribute('rgt'),
            'depth' => $this->getModel()->getAttribute('depth') - 1,
            'root' => $this->getModel()->getAttribute('root')
        ]);
    }

    public function prev()
    {
        return $this->filter([
            'rgt' => $this->getModel()->getAttribute('lft') - 1,
            'root' => $this->getModel()->getAttribute('root'),
        ]);
    }

    public function next()
    {
        return $this->filter([
            'lft' => $this->getModel()->getAttribute('rgt') + 1,
            'root' => $this->getModel()->getAttribute('root'),
        ]);
    }
    
    protected function getNextRoot()
    {
        return ($max = $this->max('root')) ? $max + 1 : 1;
    }
}