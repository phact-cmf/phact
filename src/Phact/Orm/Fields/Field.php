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

    /**
     * @var \Phact\Orm\Model
     */
    public $model;

    protected $_name;

    protected $_attribute;

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

    public function setAttribute($value)
    {
        $this->_attribute = $value;
    }

    public function getAttribute()
    {
        return $this->_attribute;
    }

    public function cleanAttribute()
    {
        $this->_attribute = null;
    }

    public function getValue($aliasConfig = null)
    {
        return $this->_attribute;
    }

    public function setValue($value, $aliasConfig = null)
    {
        $this->_attribute = $value;
        return $this->_attribute;
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
}