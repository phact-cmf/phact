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
 * @date 16/01/17 15:44
 */

namespace Phact\Orm;


class TreeManager extends Manager
{
    public $querySetClass = TreeQuerySet::class;

    /**
     * @return \Phact\Orm\TreeQuerySet
     */
    public function getQuerySet()
    {
        $qs = parent::getQuerySet();
        return $qs->order(['root', 'lft']);
    }

    public function descendants($includeSelf = false, $depth = null)
    {
        $this->getQuerySet()->descendants($includeSelf, $depth);
        return $this;
    }

    public function children($includeSelf = false)
    {
        $this->getQuerySet()->children($includeSelf);
        return $this;
    }

    public function ancestors($includeSelf = false, $depth = null)
    {
        $this->getQuerySet()->ancestors($includeSelf, $depth);
        return $this;
    }

    public function parents($includeSelf = false)
    {
        $this->getQuerySet()->parents($includeSelf);
        return $this;
    }

    public function roots()
    {
        $this->getQuerySet()->roots();
        return $this;
    }

    public function parent()
    {
        $this->getQuerySet()->parent();
        return $this;
    }

    public function prev()
    {
        $this->getQuerySet()->prev();
        return $this;
    }

    public function next()
    {
        $this->getQuerySet()->next();
        return $this;
    }
}