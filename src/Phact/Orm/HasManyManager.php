<?php
/**
 * Created by PhpStorm.
 * User: aleksandrgordeev
 * Date: 15.08.16
 * Time: 15:04
 */

namespace Phact\Orm;


class HasManyManager extends RelationManager
{

    /** @var Model field owner */
    public $ownerModel;

    /**
     * @var string
     */
    public $from;

    /**
     * @var string
     */
    public $to;

    public function getQuerySet()
    {
        $qs = parent::getQuerySet();
        $qs->filter([$this->to => $this->ownerModel->{$this->from}]);
        return $qs;
    }

}