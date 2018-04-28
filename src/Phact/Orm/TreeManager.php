<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
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
        return $this->getQuerySet()->descendants($includeSelf, $depth);
    }

    public function children($includeSelf = false)
    {
        return $this->getQuerySet()->children($includeSelf);
    }

    public function ancestors($includeSelf = false, $depth = null)
    {
        return $this->getQuerySet()->ancestors($includeSelf, $depth);
    }

    public function parents($includeSelf = false)
    {
        return $this->getQuerySet()->parents($includeSelf);
    }

    public function roots()
    {
        return $this->getQuerySet()->roots();
    }

    public function parent()
    {
        return $this->getQuerySet()->parent();
    }

    public function prev()
    {
        return $this->getQuerySet()->prev();
    }

    public function next()
    {
        return $this->getQuerySet()->next();
    }
}