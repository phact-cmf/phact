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
            'lft__gte' => $this->model->getAttribute('lft'),
            'rgt__lte' => $this->model->getAttribute('rgt'),
            'root' => $this->model->getAttribute('root')
        ])->order(['lft']);

        if ($includeSelf === false) {
            $this->exclude([
                'pk' => $this->model->pk
            ]);
        }

        if (!is_null($depth)) {
            $this->filter([
                'depth__lte' => $this->model->getAttribute('depth') + $depth
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
            'lft__lte' => $this->model->getAttribute('lft'),
            'rgt__gte' => $this->model->getAttribute('rgt'),
            'root' => $this->model->getAttribute('root')
        ])->order(['-lft']);

        if ($includeSelf === false) {
            $this->exclude([
                'pk' => $this->model->pk
            ]);
        }

        if (!is_null($depth)) {
            $qs = $qs->filter(['depth__lte' => $this->model->getAttribute('depth') - $depth]);
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
            'lft__lt' => $this->model->getAttribute('lft'),
            'rgt__gt' => $this->model->getAttribute('rgt'),
            'depth' => $this->model->getAttribute('depth') - 1,
            'root' => $this->model->getAttribute('root')
        ]);
    }

    public function prev()
    {
        return $this->filter([
            'rgt' => $this->model->getAttribute('lft') - 1,
            'root' => $this->model->getAttribute('root'),
        ]);
    }

    public function next()
    {
        return $this->filter([
            'lft' => $this->model->getAttribute('rgt') + 1,
            'root' => $this->model->getAttribute('root'),
        ]);
    }
    
    protected function getNextRoot()
    {
        return ($max = $this->max('root')) ? $max + 1 : 1;
    }
}