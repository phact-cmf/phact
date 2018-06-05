<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 03/08/16 13:28
 */

namespace Phact\Form;

use Phact\Orm\Model;

/**
 * Class ModelForm
 *
 * @property $model Model
 *
 * @package Phact\Form
 */
class ModelForm extends Form
{
    /**
     * @var Model
     */
    protected $_model = null;

    /**
     * @var Model
     */
    protected $_instance = null;


    public $only = null;

    public function getModel()
    {
        return $this->_model;
    }

    public function setModel(Model $model)
    {
        $this->_model = $model;
    }

    public function getInstance()
    {
        if (!$this->_instance) {
            $model = $this->getModel();
            $this->_instance = new $model;
        }
        return $this->_instance;
    }

    public function setInstance(Model $instance)
    {
        $this->_instance = $instance;
    }

    public function getFieldsConfigs()
    {
        $fields = [];

        $formFields = $this->getFields();
        $modelFields = $this->getModel()->getInitFields();

        $only = is_array($this->only) ? array_merge($this->only, array_keys($formFields)) : null;

        // Model fields
        foreach ($modelFields as $name => $field) {
            if (!in_array($name, $this->exclude) && (is_null($only) || in_array($name, $only))) {
                $config = null;

                if (isset($formFields[$name])) {
                    $config = $formFields[$name];
                    unset($formFields[$name]);
                } elseif ($ormConfig = $field->getFormField()) {
                    $config = $ormConfig;
                }
                if ($config) {
                    $fields[$name] = $config;
                }
            }
        }

        // Non-model fields
        foreach ($formFields as $name => $config) {
            $fields[$name] = $config;
        }

        return $fields;
    }

    public function afterInitFields()
    {
        $this->setInstanceValues();
    }

    public function setInstanceValues()
    {
        $instance = $this->getInstance();
        $fields = $instance->getFieldsList();
        foreach ($fields as $name) {
            $formField = $this->getField($name);
            $instanceField = $instance->getField($name);
            $value = $instanceField->getValue();
            if (is_null($value) && $instanceField->default) {
                $value = $instanceField->default;
            }
            if ($formField) {
                $formField->setValue($value);
            }
        }
    }

    public function beforeSetModelAttributes()
    {
    }

    public function afterSetModelAttributes()
    {
    }

    public function setInstanceAttributes($attributes)
    {
        $instance = $this->getInstance();
        $instance->setAttributes($attributes);
        return $this;
    }

    public function save($safeAttributes = [])
    {
        $instance = $this->getInstance();
        $attributes = $this->getAttributes();

        $this->beforeSetModelAttributes();
        $this->setInstanceAttributes($attributes);
        $this->setInstanceAttributes($safeAttributes);
        $this->afterSetModelAttributes();

        return $instance->save();
    }
}