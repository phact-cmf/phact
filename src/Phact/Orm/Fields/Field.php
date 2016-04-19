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
 * @date 13/04/16 07:59
 */

namespace Phact\Orm\Fields;


use Phact\Helpers\SmartProperties;

/**
 * Class Field
 *
 * @property string $name
 * @property mixed $attribute
 *
 * @package Phact\Orm\Fields
 */
class Field
{
    use SmartProperties;

    public $pk = false;

    protected $_ownerModelClass;

    /**
     * @var \Phact\Orm\Model
     */
    protected $_model;

    protected $_name;

    protected $_attribute;

    public $null = false;

    public $blank = false;

    public function getBlankValue()
    {
        return '';
    }

    public function setOwnerModelClass($modelClass)
    {
        $this->_ownerModelClass = $modelClass;
    }

    public function getOwnerModelClass()
    {
        return $this->_ownerModelClass;
    }

    public function setModel($model)
    {
        $this->_model = $model;
    }

    public function getModel()
    {
        return $this->_model;
    }

    public function setName($name)
    {
        $this->_name = $name;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getAliases()
    {
        return [];
    }

    /**
     * @return string
     */
    public function getAttributeName()
    {
        return $this->name;
    }

    /**
     * Calls only in internal methods of Model
     * such as:
     *
     * _beforeInsert()
     * _afterInsert()
     * _beforeUpdate()
     * _afterUpdate()
     * _beforeDelete()
     * _afterDelete()
     *
     * @param $value
     */
    public function setAttribute($value)
    {
        $this->_attribute = $value;
    }

    /**
     * Get raw attribute name
     *
     * @return mixed
     */
    public function getAttribute()
    {
        return $this->_attribute;
    }

    public function cleanAttribute()
    {
        $this->_attribute = null;
    }

    /**
     * Get attribute prepared for model attributes
     *
     * @param null $aliasConfig
     * @return mixed
     */
    public function getValue($aliasConfig = null)
    {
        return $this->_attribute;
    }

    /**
     * Calls when Model::setAttribute() method called,
     * include calls like:
     *
     * $model->{attribute_name} = $value;
     *
     * @param $value
     * @return mixed
     */
    public function setValue($value, $aliasConfig = null)
    {
        $this->_attribute = $value;
        return $this->_attribute;
    }

    /**
     * Value for writing to database
     */
    public function getDbPreparedValue()
    {
        $value = $this->getAttribute();
        if (is_null($value)) {
            if ($this->null) {
                return null;
            } else {
                return $this->getBlankValue();
            }
        }
        return $this->_dbPrepareValue($value);
    }

    public function getAdditionalFields()
    {
        return [];
    }

    public function beforeInsert()
    {
    }

    public function beforeUpdate()
    {
    }

    public function beforeDelete()
    {
    }

    public function afterInsert()
    {
    }

    public function afterUpdate()
    {
    }

    public function afterDelete()
    {
    }

    public function beforeSave()
    {
    }

    public function afterSave()
    {
    }

    protected function _dbPrepareValue($value)
    {
        return $value;
    }
}