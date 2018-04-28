<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 13/04/16 07:33
 */

namespace Phact\Orm;


use Phact\Exceptions\UnknownPropertyException;
use Phact\Helpers\Configurator;
use Phact\Main\Phact;
use Phact\Orm\Fields\AutoField;
use Phact\Orm\Fields\Field;

class FieldsManager
{
    protected $_fields = [];
    protected $_attributes = [];
    protected $_virtualFields = [];
    protected $_pkField;
    protected $_pkAttribute;
    protected $_aliases = [];
    protected $_className;


    public static function makeInstance($modelClass, $fields, $metaData = [])
    {
        $cacheTimeout = Phact::app()->db->getCacheFieldsTimeout();
        $key = 'PHACT__FIELDS_MANAGER_' . $modelClass;
        $manager = is_null($cacheTimeout) ? null : Phact::app()->cache->get($key);
        if (!$manager) {
            $manager = new self($modelClass, $fields, $metaData);
            if (!is_null($cacheTimeout)) {
                Phact::app()->cache->set($key, $manager, $cacheTimeout);
            }
        }
        return $manager;
    }

    public function __construct($modelClass, $fields, $metaData = [])
    {
        $this->_modelClass = $modelClass;
        $this->initFields($fields);
    }

    public function getModelClass()
    {
        return $this->_modelClass;
    }

    protected function initFields($fields)
    {
        foreach ($fields as $name => $config) {
            $field = $this->initField($name, $config);
            if ($additionalFields = $field->getAdditionalFields()) {
                foreach ($additionalFields as $additionalName => $additionalConfig) {
                    $this->_fields[$additionalName] = $this->initField($additionalName, $additionalConfig);
                }
            }
            $this->_fields[$name] = $field;
        }
        if (!$this->_pkField && !$this->has('id')) {
            $autoField = $this->initField('id', [
                'class' => AutoField::class
            ]);
            $this->_fields = array_merge([
                'id' => $autoField
            ], $this->_fields);
        }
    }

    protected function initField($name, $config)
    {
        /* @var $field \Phact\Orm\Fields\Field */
        $field = Configurator::create($config);
        $field->setName($name);
        $field->setOwnerModelClass($this->getModelClass());
        $aliases = $field->getAliases();
        $this->mergeAliases($name, $aliases);
        $attribute = $field->getAttributeName();
        if ($field->pk) {
            $this->_pkField = $name;
            $this->_pkAttribute = $attribute;
        }

         if ($attribute) {
            $this->_attributes[$name] = $attribute;
             if ($field->virtual) {
                 $this->_virtualFields[$name] = $attribute;
             }
        }

        return $field;
    }

    public function getFieldsList()
    {
        return array_keys($this->_fields);
    }

    public function getAttributesList()
    {
        return $this->_attributes;
    }

    public function getDbAttributesList()
    {
        $virtual = array_keys($this->_virtualFields);
        $dbAttributes = [];
        foreach ($this->_attributes as $key => $value) {
            if (!in_array($key, $virtual)) {
                $dbAttributes[$key] = $value;
            }
        }
        return $dbAttributes;
    }

    public function getAttributesListByFields($fields = [])
    {
        $attributes = [];
        foreach ($fields as $fieldName) {
            if (isset($this->_attributes[$fieldName])) {
                $attributes[] = $this->_attributes[$fieldName];
            }
        }
        return $attributes;
    }

    public function getPkField()
    {
        return $this->_pkField;
    }

    public function getPkAttribute()
    {
        return $this->_pkAttribute;
    }

    protected function mergeAliases($name, $aliases)
    {
        foreach ($aliases as $aliasName => $config) {
            $this->_aliases[$aliasName] = [
                'field' => $name,
                'config' => $config
            ];
        }
    }

    /**
     * @return \Phact\Orm\Fields\Field[]
     */
    public function getFields()
    {
        return $this->_fields;
    }

    /**
     * @param $name
     * @return \Phact\Orm\Fields\Field
     * @throws UnknownPropertyException
     */
    public function getField($name)
    {
        if (isset($this->_fields[$name])) {
            return $this->_fields[$name];
        } elseif ($alias = $this->getAlias($name)) {
            return $this->_fields[$alias['field']];
        } else {
            return null;
        }
    }

    /**
     * @param $attribute
     * @return \Phact\Orm\Fields\Field
     * @throws UnknownPropertyException
     */
    public function getFieldByAttribute($attribute)
    {
        $attributes = array_flip($this->_attributes);

        if (isset($attributes[$attribute])) {
            return $this->getField($attribute);
        } else {
            throw new UnknownPropertyException(strtr("Getting unknown field by attribute: {attribute}", [
                '{attribute}' => $attribute
            ]));
        }
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getAlias($name)
    {
        return isset($this->_aliases[$name]) ? $this->_aliases[$name] : null;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getAliasConfig($name)
    {
        if (isset($this->_aliases[$name])) {
            return $this->_aliases[$name]['config'];
        }
        return null;
    }

    /**
     * @param $name
     * @return string
     * @throws UnknownPropertyException
     */
    public function getFieldAttributeName($name)
    {
        if ($this->has($name)) {
            $field = $this->getField($name);
            return $field->getAttributeName();
        } else {
            throw new UnknownPropertyException(strtr("Getting name of unknown field: {field}", [
                '{field}' => $name
            ]));
        }
    }

    public function fetchField(Model $model, $name)
    {
        if ($field = $this->getField($name)) {
            return $this->prepareField($field, $model, $name);
        } else {
            throw new UnknownPropertyException(strtr("Getting value of unknown field: {field}", [
                '{field}' => $name
            ]));
        }
    }

    public function prepareField(Field $field, Model $model, $name)
    {
        $field->setModel($model);
        if ($attributeName = $field->getAttributeName()) {
            $field->setAttribute($model->getAttribute($attributeName));
            $field->setOldAttribute($model->getOldAttribute($attributeName));
        }
        return $field;
    }

    /**
     * @param $model Model
     * @param $name
     * @return mixed
     * @throws UnknownPropertyException
     */
    public function getFieldValue($model, $name)
    {
        $field = $this->getField($name);
        if ($field->rawGet && ($attributeName = $field->getAttributeName())) {
            return $model->getAttribute($attributeName);
        }
        if ($field = $this->prepareField($field, $model, $name)) {
            $alias = $this->getAliasConfig($name);
            return $field->getValue($alias);
        } else {
            throw new UnknownPropertyException(strtr("Getting value of unknown field: {field}", [
                '{field}' => $name
            ]));
        }
    }

    /**
     * @param $model
     * @param $name
     * @param $attribute
     * @param $value
     * @return mixed Attribute of field
     * @throws UnknownPropertyException
     */
    public function setFieldValue($model, $name, $value)
    {
        if ($this->has($name)) {
            $field = $this->fetchField($model, $name);
            $alias = $this->getAliasConfig($name);
            $field->setValue($value, $alias);
            return $field->getAttribute();
        } else {
            throw new UnknownPropertyException(strtr("Getting value of unknown field: {field}", [
                '{field}' => $name
            ]));
        }
    }

    public function hasField($name)
    {
        return isset($this->_fields[$name]);
    }

    public function hasAlias($name)
    {
        return isset($this->_aliases[$name]);
    }

    public function has($name)
    {
        return isset($this->_fields[$name]) || isset($this->_aliases[$name]);
    }
}