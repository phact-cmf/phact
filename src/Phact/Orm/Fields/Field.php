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

use Phact\Form\Fields\CharField;
use Phact\Form\Fields\DropDownField;

use Phact\Helpers\SmartProperties;

/**
 * Class Field
 *
 * @property string $name
 * @property mixed $attribute
 *
 * @package Phact\Orm\Fields
 */
abstract class Field
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

    protected $_oldAttribute;

    /**
     * Can field be NULL
     * @var bool
     */
    public $null = false;

    /**
     * Can field be blank/empty
     * @var bool
     */
    public $blank = false;

    /**
     * Unsigned operator for table column
     * @var bool
     */
    public $unsigned = false;

    /**
     * Zerofill operator for table column
     * @var bool
     */
    public $zerofill = false;

    /**
     * @var mixed
     */
    public $default = null;

    /**
     * @var array
     */
    public $choices = [];

    /**
     * Can edit with forms
     * @var bool
     */
    public $editable = true;

    /**
     * Label
     * @var bool
     */
    public $label = '';

    /**
     * Help text
     * @var bool
     */
    public $hint = '';

    /**
     * Has field attribute in model table
     * @var bool
     */
    public $virtual = false;

    /**
     * @return string
     */
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
     * @return bool
     */
    public function hasDbAttribute()
    {
        return (bool) $this->getAttributeName();
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

    public function setOldAttribute($value)
    {
        $this->_oldAttribute = $value;
    }

    public function getOldAttribute()
    {
        return $this->_oldAttribute;
    }

    public function getIsChanged()
    {
        return $this->_attribute !== $this->_oldAttribute;
    }

    /**
     * Set model attribute
     *
     * @param $value
     */
    public function setModelAttribute($value)
    {
        $this->getModel()->setAttribute($this->getAttributeName(), $value);
    }

    /**
     * Has model attribute
     *
     * @return bool
     */
    public function hasModelAttribute()
    {
        return $this->getModel()->hasAttribute($this->getAttributeName());
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

    public function cleanOldAttribute()
    {
        $this->_oldAttribute = null;
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
     * @param null $aliasConfig
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
            } elseif ($this->default) {
                return $this->default;
            } else {
                return $this->getBlankValue();
            }
        }
        return $this->dbPrepareValue($value);
    }

    public function setDefaultDbValue()
    {
        if ($this->hasDbAttribute()) {
            if (is_null($this->attribute)) {
                if ($this->null) {
                    $this->setAttribute(null);
                } else {
                    $value = $this->getBlankValue();
                    $this->setAttribute($value);
                }
            }
        }
    }

    public function setFromDbValue($value)
    {
        $attribute = $this->attributePrepareValue($value);
        $this->setAttribute($attribute);
        $this->setOldAttribute($attribute);
    }

    public function getAdditionalFields()
    {
        return [];
    }

    public function beforeInsert()
    {
        $this->setDefaultDbValue();
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


    /**
     * Prepare attribute value for database value
     * Reverse function for attributePrepareValue
     *
     * @param $value
     * @return mixed
     */
    protected function dbPrepareValue($value)
    {
        return $value;
    }

    /**
     * Prepare db database for model attribute
     * Reverse function for dbPrepareValue
     *
     * @param $value
     * @return mixed
     */
    protected function attributePrepareValue($value)
    {
        return $value;
    }

    /**
     * @return string
     */
    abstract public function getSqlType();


    /**
     * Getting config form field
     * @return null
     */
    public function getFormField()
    {
        return $this->setUpFormField();
    }

    /**
     * Getting display representations of value
     * @param null $default
     * @return mixed|null
     */
    public function getChoiceDisplay($default = null)
    {
        $attribute = $this->getAttribute();
        if ($this->choices && isset($this->choices[$attribute])) {
            return $this->choices[$attribute];
        }
        return $default;
    }

    /**
     * Setting up form field
     *
     * @param array $config
     * @return null
     */
    public function setUpFormField($config = [])
    {
        if (!$this->editable) {
            return null;
        }

        $class = isset($config['class']) ? $config['class'] : null;

        if ($this->choices && !$class) {
            $class = DropDownField::class;
        }

        if (!$class) {
            $class = CharField::class;
        }

        return array_merge([
            'class' => $class,
            'required' => !$this->null && !$this->blank,
            'label' => $this->label,
            'hint' => $this->hint,
            'value' => $this->default,
            'choices' => $this->choices
        ], $config);
    }
}