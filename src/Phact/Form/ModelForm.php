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

        // Model fields
        foreach ($modelFields as $name => $field) {
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
        $instanceAttributes = $this->getInstance()->getAttributes();
        foreach ($instanceAttributes as $name => $value) {
            $field = $this->getField($name);
            if ($field && !is_null($value)) {
                $field->setValue($value);
            }
        }
    }

    public function beforeSetModelAttributes()
    {
    }

    public function afterSetModelAttributes()
    {
    }

    public function save()
    {
        $instance = $this->getInstance();
        $attributes = $this->getAttributes();

        $this->beforeSetModelAttributes();
        $instance->setAttributes($attributes);
        $this->afterSetModelAttributes();

        return $instance->save();
    }
}