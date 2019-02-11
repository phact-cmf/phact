<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 02/08/16 07:27
 */

namespace Phact\Form;

use Phact\Form\Configuration\ConfigurationProvider;
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

    protected $_name;

    /**
     * Form name
     *
     * @var string
     */
    public $prefix = '';

    /**
     * Prefix for id attribute of fields
     *
     * @var string
     */
    public $idPrefix = '';

    /**
     * Key of form. For multiple render same forms. 
     * Example: default render - Form[field], with key render - Form[key][field]
     *
     * @var string|int
     */
    public $key;

    public function __construct($config = [])
    {
        Configurator::configure($this, $config);
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return [];
    }

    /**
     * Fields preparation
     *
     * @return array
     */
    public function getFieldsConfigs()
    {
        return $this->getFields();
    }

    /**
     * @return Field[]
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    public function getInitFields()
    {
        if (is_null($this->_initFields)) {
            $this->_initFields = [];
            foreach ($this->getFieldsConfigs() as $name => $fieldConfiguration) {
                if (!in_array($name, $this->exclude)) {
                    /** @var Field $field */

                    list($fieldClass, $fieldConfiguration) = Configurator::split($fieldConfiguration);

                    $configuration = ConfigurationProvider::getInstance()->getManager();
                    $arguments = [];
                    if (isset($fieldConfiguration['arguments'])) {
                        $arguments = $fieldConfiguration['arguments'];
                        unset($fieldConfiguration['arguments']);
                    }
                    $field = $configuration->getContainer()->construct($fieldClass, $arguments);
                    Configurator::configure($field, array_merge($fieldConfiguration, [
                        'form' => $this,
                        'name' => $name
                    ]));
                    $field->init();
                    $this->_initFields[$name] = $field;
                }
            }
            $this->afterInitFields();
        }
        return $this->_initFields;
    }

    /**
     * Calls after fields inited
     */
    public function afterInitFields()
    {
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

    public function hasField($name)
    {
        $fields = $this->getInitFields();
        return isset($fields[$name]) ? true : false;
    }

    public function fill($data, $files = [])
    {
        $name = $this->getName();
        $filled = false;
        if ($this->hasFiles($files)) {
            $preparedFiles = $this->prepareFiles($files[$name]);
            $this->setAttributes($preparedFiles);
            $filled = true;
        }
        if (isset($data[$name])) {
            $this->setAttributes($data[$name]);
            $filled = true;
        }
        return $filled;
    }

    public function prepareFiles($_files = [])
    {
        $files = [];
        foreach (array_keys($_files) as $keyProp => $prop) {
            $propValue = $_files[$prop];
            foreach ($propValue as $key => $value) {
                $value = (!is_array($value)) ? (array)$value : $value;
                foreach ($value as $keyValue => $val) {
                    $files[$key][$prop] = $val;
                }
            }
        }
        return $files;
    }

    public function hasFiles($files)
    {
        $has = true;

        if (empty($files)) {
            $has = false;
        }

        if ($has && !isset($files[$this->getName()])) {
            $has = false;
        }

        if($has){
            $filesData = $files[$this->getName()];

            if (isset($filesData['error'])) {
                $has = false;
                $errors = $filesData['error'];
                foreach ($errors as $error) {
                    if ($error != UPLOAD_ERR_NO_FILE) {
                        $has = true;
                        break;
                    }
                }
            }
        }

        return $has;
    }
    
    public function setRawAttributes($attributes)
    {
        foreach ($attributes as $name => $value)
        {
            $this->setAttribute($name, $value);
        }
        return $this;
    }

    public function setAttribute($name, $value)
    {
        if ($field = $this->getField($name)) {
            $field->setValue($value);
        }
    }

    public function getRawAttributes()
    {
        $attributes = [];
        $fields = $this->getInitFields();
        foreach ($fields as $name => $field) {
            $attributes[$name] = $field->getValue();
        }
        return $attributes;
    }

    public function setAttributes($attributes)
    {
        foreach ($attributes as $name => $value)
        {
            if (($field = $this->getField($name)) && (!$field->readonly)) {
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
            if (!$field->readonly) {
                $attributes[$name] = $field->getValue();
            }
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
        if (!$this->_name) {
            $this->_name = $this->prefix . self::classNameShort();
        }
        return $this->_name;
    }

    public function clean($attributes)
    {

    }
    
    public function render($template = null, $fields = [])
    {
        if (!$template) {
            $template = 'forms/default.tpl';
        }
        if (!$fields) {
            $fields = array_keys($this->getInitFields());
        }
        return $this->renderTemplate($template, [
            'form' => $this,
            'fields' => $fields
        ]);
    }
}