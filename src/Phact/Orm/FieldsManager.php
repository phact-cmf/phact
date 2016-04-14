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
 * @date 13/04/16 07:33
 */

namespace Phact\Orm;


use Phact\Exceptions\UnknownPropertyException;
use Phact\Helpers\Configurator;
use Phact\Orm\Fields\AutoField;

class FieldsManager
{
    protected $_fields = [];
    protected $_dbFields = [];
    protected $_virtualFields = [];
    protected $_pkField;
    protected $_aliases = [];

    public function __construct($fields, $metaData = [])
    {
        $this->initFields($fields);
    }

    protected function initFields($fields)
    {
        foreach ($fields as $name => $config) {
            $this->_fields[$name] = $this->initField($name, $config);
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
        $aliases = $field->getAliases();
        $this->mergeAliases($name, $aliases);
        if ($field->pk) {
            $this->_pkField = $name;
        }
        return $field;
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
        if ($this->hasField($name)) {
            $field = $this->_fields[$name];
            return $field;
        } elseif ($this->hasAlias($name)) {
            $alias = $this->getAlias($name);
            $name = $alias['field'];
            return $this->_fields[$name];
        } else {
            throw new UnknownPropertyException(strtr("Getting unknown field: {field}", [
                '{field}' => $name
            ]));
        }
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getAlias($name)
    {
        $alias = null;
        if ($this->hasAlias($name)) {
            return $this->_aliases[$name];
        }
        return null;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getAliasConfig($name)
    {
        $alias = $this->getAlias($name);
        if ($alias) {
            return $alias['config'];
        }
        return $alias;
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

    /**
     * @param $name
     * @param $attribute
     * @return mixed
     * @throws UnknownPropertyException
     */
    public function getFieldValue($name, $attribute)
    {
        if ($this->has($name)) {
            $field = $this->getField($name);
            $alias = $this->getAliasConfig($name);
            $field->setAttribute($attribute);
            return $field->getValue($alias);
        } else {
            throw new UnknownPropertyException(strtr("Getting value of unknown field: {field}", [
                '{field}' => $name
            ]));
        }
    }

    /**
     * @param $name
     * @param $value
     * @return mixed
     * @throws UnknownPropertyException
     */
    public function setFieldValue($name, $value)
    {
        if ($this->has($name)) {
            $field = $this->getField($name);
            $alias = $this->getAliasConfig($name);
            $field->cleanAttribute();
            return $field->setValue($value, $alias);
        } else {
            throw new UnknownPropertyException(strtr("Getting value of unknown field: {field}", [
                '{field}' => $name
            ]));
        }
    }

    public function hasField($name)
    {
        return array_key_exists($name, $this->_fields);
    }

    public function hasAlias($name)
    {
        return array_key_exists($name, $this->_aliases);
    }

    public function has($name)
    {
        return $this->hasField($name) || $this->hasAlias($name);
    }
}