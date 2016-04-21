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
class ManyToManyField extends RelationField
{
    /**
     * Related model field
     *
     * @var string
     */
    protected $_to = 'id';

    /**
     * Current model field
     *
     * @var string
     */
    protected $_from = 'id';

    protected $_through;
    protected $_throughFrom;
    protected $_throughTo;
    protected $_throughName;

    protected $_throughModel;

    public $reverse = false;

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
        return $this->_to;
    }

    public function setTo($to)
    {
        $this->_to = $to;
    }

    public function getThrough()
    {
        return $this->_through;
    }

    public function setThrough($through)
    {
        $this->_through = $through;
    }

    public function getThroughName()
    {
        if (!$this->_throughName && ($through = $this->getThrough())) {
            $throughName = $through::classNameUnderscore();
            $this->_throughName = $throughName;
        }
        return $this->_throughName;
    }

    public function setThroughName($throughName)
    {
        $this->_throughName = $throughName;
    }

    public function getThroughTo()
    {
        if (!$this->_throughTo) {
            $class = $this->modelClass;
            $modelName = $class::classNameUnderscore();
            $toName = $this->getTo();
            $this->_throughTo = "{$modelName}_{$toName}";
        }
        return $this->_throughTo;
    }

    public function setThroughTo($throughTo)
    {
        $this->_throughTo = $throughTo;
    }

    public function getThroughFrom()
    {
        if (!$this->_throughFrom) {
            $model = $this->getOwnerModelClass();
            $modelName = $model::classNameUnderscore();
            $toName = $this->getFrom();
            $this->_throughFrom = "{$modelName}_{$toName}";
        }
        return $this->_throughFrom;
    }

    public function setThroughFrom($throughFrom)
    {
        $this->_throughFrom = $throughFrom;
    }

    public function getThroughTableName()
    {
        if ($through = $this->getThrough()) {
            return $through::getTableName();
        } else {
            $model = $this->getOwnerModelClass();
            $modelClass = $this->modelClass;
            $names = [$model::getTableName(), $modelClass::getTableName()];
            sort($names);
            return implode('_', $names);
        }

    }

    public function getAdditionalFields()
    {
        if ($through = $this->getThrough()) {
            $throughName = $this->getThroughName();
            return [
                "$throughName" => [
                    'class' => HasManyField::class,
                    'modelClass' => $through,
                    'throughFor' => $this->getName(),
                    'from' => $this->getFrom(),
                    'to' => $this->getThroughFrom()
                ]
            ];
        } else {
            return [];
        }
    }

    public function getAttributeName()
    {
       return null;
    }

    public function getRelationJoins()
    {
        $relationModelClass = $this->modelClass;
        if ($throughName = $this->getThroughName()) {
            return [
                $throughName,
                [
                    'table' => $relationModelClass::getTableName(),
                    'from' => $this->getThroughTo(),
                    'to' => $this->getTo()
                ]
            ];
        } else {
            return [
                [
                    'table' => $this->getThroughTableName(),
                    'from' => $this->getFrom(),
                    'to' => $this->getThroughFrom()
                ],
                [
                    'table' => $relationModelClass::getTableName(),
                    'from' => $this->getThroughTo(),
                    'to' => $this->getTo()
                ]
            ];
        }

    }
}