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
 * @date 19/04/16 08:29
 */

namespace Phact\Orm\Fields;

/**
 * Class HasManyField
 *
 * @property $to string Related model field
 * @property $from string Current model field
 *
 * @package Phact\Orm\Fields
 */
class HasManyField extends RelationField
{
    protected $_to = null;
    protected $_from = 'id';
    protected $_throughFor;

    public function setThroughFor($throughFor)
    {
        $this->_throughFor = $throughFor;
    }

    public function getThroughFor()
    {
        return $this->_throughFor;
    }

    public function getFrom()
    {
        return $this->_from;
    }

    public function setFrom($from)
    {
        $this->_from = $from;
    }

    public function getTo()
    {
        if ($this->_to) {
            return $this->_to;
        }
        $model = $this->getOwnerModelClass();
        $name = $model::classNameUnderscore();
        $from = $this->_from;
        return "{$name}_{$from}";
    }

    public function setTo($to)
    {
        $this->_to = $to;
    }

    public function getAttributeName()
    {
       return null;
    }

    public function getRelationJoins()
    {
        $relationModelClass = $this->modelClass;
        return [
            [
                'table' => $relationModelClass::getTableName(),
                'from' => $this->getFrom(),
                'to' => $this->getTo()
            ]
        ];
    }
}