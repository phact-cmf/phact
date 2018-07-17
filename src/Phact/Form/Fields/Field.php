<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 02/08/16 07:31
 */

namespace Phact\Form\Fields;


use Phact\Exceptions\InvalidConfigException;
use Phact\Form\Form;
use Phact\Helpers\ClassNames;
use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;
use Phact\Template\Renderer;
use Phact\Validators\FormFieldValidator;
use Phact\Validators\RequiredValidator;
use Phact\Validators\Validator;

/**
 * Class Form
 *
 * @property $hasErrors bool
 * @property $valid bool
 * @property $isRequired bool
 *
 * @package Phact\Form
 */
abstract class Field
{
    use SmartProperties, Renderer, ClassNames;

    /**
     * @var string
     */
    protected $_name;

    /**
     * @var Form
     */
    protected $_form;

    /**
     * HTML attributes of input
     * @var array
     */
    protected $_attributes = [];

    /**
     * Class, that appends to all blocks if field is required
     * @var string
     */
    public $requiredClass = 'required';

    /**
     * Class, that appends to all blocks if field is invalid
     * @var string
     */
    public $invalidClass = 'invalid';

    /**
     * @var string
     */
    public $errorsClass = 'errors';

    /**
     * @var string
     */
    public $hintClass = 'hint';

    /**
     * @var string
     */
    public $labelClass = 'label';

    /**
     * @var mixed
     */
    protected $_value;

    /**
     * @var string
     */
    protected $_label;

    /**
     * @var string
     */
    protected $_hint;

    /**
     * @var array
     */
    protected $_errors = [];

    /**
     * Required field
     * @var bool
     */
    public $required = false;

    /**
     * Readonly field
     * @var bool
     */
    public $readonly = false;

    /**
     * Required field
     * @var bool
     */
    public $requiredMessage = null;

    /**
     * Validators
     *
     * @var array
     */
    protected $_validators = [];

    /**
     * @var string
     */
    public $inputTemplate = 'forms/field/default/input.tpl';

    /**
     * @var string
     */
    public $errorsTemplate = 'forms/field/default/errors.tpl';

    /**
     * @var string
     */
    public $labelTemplate = 'forms/field/default/label.tpl';

    /**
     * @var string
     */
    public $hintTemplate = 'forms/field/default/hint.tpl';

    /**
     * @var string
     */
    public $fieldTemplate = 'forms/field/default/field.tpl';

    /**
     * @var bool
     */
    public $multiple = false;

    /**
     * @var array
     */
    public $choices = [];

    /**
     * Checks required field
     *
     * @return bool
     */
    public function getIsRequired()
    {
        if ($this->required) {
            return true;
        }
        foreach ($this->_validators as $validator)
        {
            if ($validator instanceof RequiredValidator) {
                return true;
            }
        }
        return false;
    }

    public function setValidators($validators)
    {
        $this->_validators = $validators;
        return $this;
    }

    public function getValidators()
    {
        return $this->_validators;
    }

    public function init()
    {
        $this->setDefaultValidators();
        $this->initValidators();
    }

    public function setDefaultValidators()
    {
        if ($this->required) {
            $this->_validators[] = new RequiredValidator($this->requiredMessage);
        }
    }

    public function initValidators()
    {
        foreach ($this->_validators as $validator)
        {
            if ($validator instanceof FormFieldValidator)
            {
                $validator->setField($this);
            }
        }
    }

    /**
     * Set HTML attributes
     * @param $attributes
     * @return $this
     * @throws InvalidConfigException
     */
    public function setAttributes($attributes)
    {
        if (!is_array($attributes)) {
            throw new InvalidConfigException('Attributes must be an array');
        }
        $this->_attributes = $attributes;
        return $this;
    }

    /**
     * Get HTML attributes
     * @return array
     */
    public function getAttributes()
    {
        return $this->_attributes;
    }

    public function getCommonClasses()
    {
        $classes = [];
        if ($this->isRequired) {
            $classes[] = $this->requiredClass;
        }
        if ($this->hasErrors) {
            $classes[] = $this->invalidClass;
        }
        return implode(' ', $classes);
    }

    /**
     * Get HTML attributes of input with required, invalid classes and other additional information
     * @return array
     */
    public function getAttributesInput()
    {
        $attributes = $this->getAttributes();
        $attributes = $this->extendAttribute($attributes, 'class', $this->getCommonClasses());
        if ($this->readonly) {
            $attributes['readonly'] = 'readonly';
        }
        return $attributes;
    }

    /**
     * @return array
     */
    public function getAttributesCommon()
    {
        return [
            'class' => $this->getCommonClasses()
        ];
    }

    public function getAttributesLabel()
    {
        $attributes = $this->getAttributesCommon();
        $attributes = $this->extendAttribute($attributes, 'class', $this->labelClass);
        return $attributes;
    }

    public function getAttributesErrors()
    {
        $attributes = $this->getAttributesCommon();
        $attributes = $this->extendAttribute($attributes, 'class', $this->errorsClass);
        return $attributes;
    }

    public function getAttributesHint()
    {
        $attributes = $this->getAttributesCommon();
        $attributes = $this->extendAttribute($attributes, 'class', $this->hintClass);
        return $attributes;
    }

    /**
     * Builds HTML attributes of input
     */
    public function buildAttributesInput()
    {
        $attributes = $this->getAttributesInput();
        return $this->buildAttributes($attributes);
    }

    /**
     * Builds HTML attributes of label
     */
    public function buildAttributesLabel()
    {
        $attributes = $this->getAttributesLabel();
        return $this->buildAttributes($attributes);
    }

    /**
     * Builds HTML attributes of errors
     */
    public function buildAttributesErrors()
    {
        $attributes = $this->getAttributesErrors();
        return $this->buildAttributes($attributes);
    }

    /**
     * Builds HTML attributes of hint
     */
    public function buildAttributesHint()
    {
        $attributes = $this->getAttributesHint();
        return $this->buildAttributes($attributes);
    }

    /**
     * Builds HTML attributes of errors to hint
     */
    public function buildLabelAttributes()
    {
        $attributes = $this->getAttributesLabel();
        return $this->buildAttributes($attributes);
    }

    public function extendAttribute($attributes, $name, $value, $glue = ' ')
    {
        if ($value) {
            $attribute = isset($attributes[$name]) ? $attributes[$name] : '';
            if ($attribute) {
                $attribute .= $glue;
            }
            $attributes[$name] = $attribute . $value;
        }
        return $attributes;
    }

    /**
     * @param $attributes
     * @return string
     * @throws InvalidConfigException
     */
    public function buildAttributes($attributes)
    {
        $builtAttributes = '';
        foreach ($attributes as $key => $value)
        {
            if (!is_scalar($value)) {
                throw new InvalidConfigException('Values of attributes must be a scalar types');
            }

            if ($builtAttributes) {
                $builtAttributes .= ' ';
            }
            $builtAttributes .= htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
        return $builtAttributes;
    }

    /**
     * Set/merge HTML attribute
     *
     * @param $key
     * @param $value
     * @param string $glue
     * @return $this
     * @throws InvalidConfigException
     */
    public function setAttribute($key, $value, $glue = ' ')
    {
        if (!is_scalar($key) && !is_scalar($value)) {
            throw new InvalidConfigException('Value and key of attribute must be a scalar types');
        }
        if (is_null($glue)) {
            $this->_attributes[$key] = $value;
        } else {
            $attribute = $this->getAttribute($key, '');
            $this->_attributes[$key] = implode($glue, [$attribute, $value]);
        }
        return $this;
    }

    /**
     * Get HTML attribute
     *
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function getAttribute($key, $default = null)
    {
        return isset($this->_attributes[$key]) ? $this->_attributes[$key] : $default;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    /**
     * @return Form
     */
    public function getForm()
    {
        return $this->_form;
    }

    public function setForm($form)
    {
        $this->_form = $form;
        return $this;
    }

    public function setValue($value)
    {
        $this->_value = $value;
        return $this;
    }

    public function getValue()
    {
        return $this->_value;
    }

    public function setLabel($label)
    {
        $this->_label = $label;
        return $this;
    }

    public function getLabel()
    {
        return $this->_label;
    }

    public function setHint($hint)
    {
        $this->_hint = $hint;
        return $this;
    }

    public function getHint()
    {
        return $this->_hint;
    }

    public function clearValue()
    {
        $this->setValue(null);
    }

    public function validate()
    {
        $value = $this->getValue();
        foreach ($this->_validators as $validator) {
            $error = true;

            if ($validator instanceof Validator) {
                $error = $validator->validate($value);
            } elseif (is_callable($validator)) {
                $error = $validator($value);
            }

            if (is_string($error)) {
                $this->_errors[] = $error;
            } elseif (is_array($error)) {
                $this->_errors = array_merge($this->_errors, $error);
            }
        }

        return !$this->hasErrors;
    }

    public function addError($error)
    {
        if (!is_array($error)) {
            $error = [$error];
        }
        $this->_errors = array_merge($this->_errors, $error);
    }

    public function clearErrors()
    {
        $this->_errors = [];
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    public function getHasErrors()
    {
        return !empty($this->_errors);
    }

    /**
     * Render value for output
     * Date, datetime for example
     *
     * Value: 2012-09-23
     * Render value: 23.09.2012
     *
     * @return mixed
     */
    public function getRenderValue()
    {
        return $this->getValue();
    }

    public function renderInput()
    {
        return $this->renderTemplate($this->inputTemplate, [
            'field' => $this,
            'html' => $this->buildAttributesInput(),
            'id' => $this->getHtmlId(),
            'value' => $this->getRenderValue(),
            'name' => $this->getHtmlName()
        ]);
    }

    public function renderErrors()
    {
        return $this->renderTemplate($this->errorsTemplate, [
            'field' => $this,
            'html' => $this->buildAttributesErrors(),
            'id' => $this->getHtmlId(),
            'errors' => $this->getErrors()
        ]);
    }

    public function renderLabel()
    {
        return $this->renderTemplate($this->labelTemplate, [
            'field' => $this,
            'html' => $this->buildAttributesLabel(),
            'id' => $this->getHtmlId(),
            'label' => $this->getLabel()
        ]);
    }

    public function renderHint()
    {
        return $this->renderTemplate($this->hintTemplate, [
            'field' => $this,
            'html' => $this->buildAttributesHint(),
            'id' => $this->getHtmlId(),
            'hint' => $this->getHint()
        ]);
    }

    public function render()
    {
        return $this->renderTemplate($this->fieldTemplate, [
            'label' => $this->renderLabel(),
            'input' => $this->renderInput(),
            'errors' => $this->renderErrors(),
            'hint' => $this->renderHint()
        ]);
    }

    public function getHtmlId()
    {
        $form = $this->getForm();
        $key = $form->key ? "_{$form->key}_" : '';
        return $form->idPrefix . $form->getName() . $key . '_' . $this->getName();
    }

    public function getHtmlName()
    {
        $form = $this->getForm();
        $key = $form->key ? "[{$form->key}]" : '';
        $name = $form->getName() . $key . "[{$this->getName()}]";
        return $this->multiple ? $name . '[]' : $name;
    }
}