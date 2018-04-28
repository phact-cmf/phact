<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 13/04/16 08:11
 */

namespace Phact\Orm\Fields;

use Phact\Form\Fields\DropDownField;
use Phact\Orm\Model;
use Phact\Orm\QuerySet;

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
    public $rawSet = true;

    protected $_to = 'id';
    protected $_from = null;

    const CASCADE = 1;
    const SET_NULL = 2;
    const NO_ACTION = 3;
    const RESTRICT = 4;
    const SET_DEFAULT = 5;

    public $onUpdate = self::CASCADE;
    public $onDelete = self::CASCADE;

    /**
     * Attribute of related model that contains name
     * @var string|null
     */
    public $nameAttribute = null;

    public function getFrom()
    {
        if (!$this->_from) {
            $name = $this->getName();
            $to = $this->getTo();
            $this->_from = "{$name}_{$to}";
        }
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
            if ($aliasConfig == 'raw' || !is_object($value)) {
                $this->setRawValue($value);
            } else {
                $this->setObjectValue($value);
            }
        } else {
            $this->_attribute = null;
        }
    }

    public function setRawValue($value)
    {
        if (!is_string($value) && !is_int($value) && !is_null($value)) {
            throw new \InvalidArgumentException("Raw value for ForeignField must be a string, int or null");
        }
        if ($value === '') {
            $value = null;
        }
        $this->_attribute = $value;
    }

    public function setObjectValue($value)
    {
        if (!is_a($value, $this->getRelationModelClass())) {
            throw new \InvalidArgumentException("Object value for ForeignField must be instance of {$this->getRelationModelClass()}");
        }
        $this->_attribute = $value->{$this->to};
    }
    
    protected function fetchModel()
    {
        $model = $this->getModel();
        if ($withModel = $model->getWithModel($this->name)) {
            return $withModel;
        }
        $value = $this->_attribute;
        $class = $this->getRelationModelClass();
        if (!is_object($value)) {
            return $class::objects()->filter([
                $this->getTo() => $value
            ])->get();
        } else {
            return $value;
        }
    }

    public function getRelationJoins()
    {
        $relationModelClass = $this->getRelationModelClass();
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
        $relationModelClass = $this->getRelationModelClass();
        /** @var Model $relationModel */
        $relationModel = new $relationModelClass();
        $field = $relationModel->getField($to);
        return $field->getSqlType();
    }

    public function attributePrepareValue($value)
    {
        return isset($value) ? (int) $value : null;
    }

    public function dbPrepareValue($value)
    {
        if ($value instanceof Model) {
            $value = $value->pk;
        }
        return $value ? (int) $value : null;
    }

    public function setUpFormField($config = [])
    {
        $config['class'] = DropDownField::class;
        $choices = [];
        if (!$this->getIsRequired()) {
            $choices[''] = '';
        }
        $class = $this->getRelationModelClass();
        /** @var QuerySet $qs */
        $qs = $class::objects()->getQuerySet();
        if ($this->nameAttribute) {
            $choices = $choices + $qs->choices('pk', $this->nameAttribute);
        } else {
            $objects = $qs->all();
            foreach ($objects as $object) {
                $choices[$object->pk] = (string) $object;
            }
        }
        $config['choices'] = $choices;
        return parent::setUpFormField($config);
    }
}