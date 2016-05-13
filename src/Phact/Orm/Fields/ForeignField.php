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
 * @date 13/04/16 08:11
 */

namespace Phact\Orm\Fields;

use Phact\Orm\Model;

/**
 * Class ForeignField
 * 
 * @property $to string Related model field
 * @property $from string Current model field
 *
 * @package Phact\Orm\Fields
 */
class ForeignField extends RelationField
{
    protected $_to = 'id';
    protected $_from = null;

    public function getFrom()
    {
        if ($this->_from) {
            return $this->_from;
        }
        $name = $this->getName();
        $to = $this->to;
        return "{$name}_{$to}";
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

    public function getAttributeName()
    {
        return $this->getFrom();
    }

    public function getAliases()
    {
        $attributeName = $this->getAttributeName();
        return [
            $attributeName => 'raw'
        ];
    }

    public function getValue($aliasConfig = null)
    {
        return $aliasConfig == 'raw' ? $this->attribute : $this->fetchModel();
    }

    public function setValue($value, $aliasConfig = null)
    {
        if (!is_null($value)) {
            if ($aliasConfig == 'raw') {
                if (!is_string($value) && !is_int($value) && !is_null($value)) {
                    throw new \InvalidArgumentException("Value for raw ForeignField must be a string, int or null");
                }
                return $value;
            } else {
                if (!is_object($value) || !is_a($value, $this->modelClass)) {
                    throw new \InvalidArgumentException("Value for ForeignField must be instance of {class}");
                }
                return $value->{$this->to};
            }
        } else {
            return null;
        }
    }
    
    protected function fetchModel()
    {
        $value = $this->_attribute;
        $class = $this->modelClass;
        return new Model();
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

    public function getSqlType()
    {
        $to = $this->getTo();
        $relationModelClass = $this->modelClass;
        /** @var Model $relationModel */
        $relationModel = new $relationModelClass();
        $field = $relationModel->getField($to);
        return $field->getSqlType();
    }
}