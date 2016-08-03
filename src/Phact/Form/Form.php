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
 * @date 02/08/16 07:27
 */

namespace Phact\Form;
use Phact\Form\Fields\Field;
use Phact\Helpers\ClassNames;
use Phact\Helpers\Configurator;
use Phact\Helpers\SmartProperties;
use Phact\Helpers\Text;
use Phact\Template\Renderer;

/**
 * Class Form
 *
 * @property $hasErrors bool
 * @property $valid bool
 *
 * @package Phact\Form
 */
abstract class Form
{
    use SmartProperties, ClassNames, Renderer;

    public $exclude = [];

    protected $_initFields;

    /**
     * Prefix
     * @TODO
     *
     * @var string
     */
    public $prefix = '';
    /**
     * @return array
     */
    public function getFields()
    {
        return [];
    }

    /**
     * @return Field[]
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    public function getInitFields()
    {
        if (is_null($this->_initFields)) {
            $this->_initFields = [];
            foreach ($this->getFields() as $name => $fieldConfig) {
                if (!in_array($name, $this->exclude)) {
                    /** @var Field $field */
                    $field = Configurator::create($fieldConfig);
                    $field->setName($name);
                    $field->setForm($this);
                    $this->_initFields[$name] = $field;
                }
            }
        }
        return $this->_initFields;
    }

    /**
     * @param $name
     * @return null|Field
     */
    public function getField($name)
    {
        $fields = $this->getInitFields();
        return isset($fields[$name]) ? $fields[$name] : null;
    }

    public function fill($data, $files = [])
    {
        $name = $this->getName();
        if (isset($data[$name])) {
            $this->setAttributes($data[$name]);
            return true;
        }
        return false;
    }

    public function setAttributes($attributes)
    {
        $fields = $this->getInitFields();
        foreach ($attributes as $name => $value)
        {
            if (isset($fields[$name]) && ($field = $fields[$name])) {
                $field->setValue($value);
            }
        }
        return $this;
    }

    public function getAttributes()
    {
        $attributes = [];
        $fields = $this->getInitFields();
        foreach ($fields as $name => $field) {
            $attributes[$name] = $field->getValue();
        }
        return $attributes;
    }

    public function clearAttributes()
    {
        $fields = $this->getInitFields();
        foreach ($fields as $field) {
            $field->clearValue();
        }
    }

    public function getValid()
    {
        $fields = $this->getInitFields();
        foreach ($fields as $name => $field)
        {
            $field->clearErrors();
            $value = $field->getValue();
            $cleanMethod = 'clean' . Text::ucfirst($name);
            if (method_exists($this, $cleanMethod)) {
                $value = $this->{$cleanMethod}($value);
                $field->setValue($value);
            }
            $field->validate();
        }
        $attributes = $this->getAttributes();
        $this->clean($attributes);
        return !$this->getHasErrors();
    }

    public function getErrors()
    {
        $errors = [];
        $fields = $this->getInitFields();
        foreach ($fields as $name => $field)
        {
            $fieldErrors = $field->getErrors();
            if (!empty($fieldErrors)) {
                $errors[$name] = $fieldErrors;
            }
        }
        return $errors;
    }

    public function getHasErrors()
    {
        return !empty($this->getErrors());
    }

    public function addError($field, $error)
    {
        $initField = $this->getField($field);
        if ($initField) {
            $initField->addError($error);
        }
    }

    public function getName()
    {
        return $this->prefix . self::classNameShort();
    }

    public function clean($attributes)
    {

    }
    
    public function render($template = 'forms/default.tpl')
    {
        return $this->renderTemplate($template, [
            'form' => $this
        ]);
    }
}