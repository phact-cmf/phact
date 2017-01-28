<?php
/**
 * Created by PhpStorm.
 * User: aleksandrgordeev
 * Date: 10.08.16
 * Time: 0:42
 */

namespace Phact\Form\Fields;


use Phact\Form\ModelForm;
use Phact\Helpers\FileHelper;
use Phact\Main\Phact;
use Phact\Storage\Files\StorageFile;
use Phact\Storage\Files\UploadedFile;
use Phact\Storage\Storage;
use Phact\Storage\StorageManager;
use Phact\Validators\UploadFileValidator;

class FileField extends Field
{

    /**
     * @var array accept mime types of file. HTML5 attribute in field
     * See more at Phact\Helpers\FileHelper line 543.
     */
    public $accept = [];

    /**
     * @var null|int. Pass if need check size
     */
    public $maxSize;

    public $inputTemplate = 'forms/field/file/input.tpl';


    public function setDefaultValidators()
    {
        parent::setDefaultValidators();
        $this->_validators[] = new UploadFileValidator(FileHelper::getExtensionsFromMimes($this->accept), $this->maxSize);
    }


    public function getRenderValue()
    {
        $value = $this->getValue();
        if ($value instanceof StorageFile) {
            return $value->getPath();
        }
        return null;
    }

    /**
     * @param $value
     * @return $this
     * Form, call method 2 points, when she filled first from $_FILES and second when she filled from $_POST
     */
    public function setValue($value)
    {
        /** Filed from instance as field */
        if ($value instanceof \Phact\Orm\Fields\FileField) {
            $value = $value->attribute;
        }

        /** Filed from instance */
        if ($value instanceof StorageFile) {
            $this->_value = $value;
        }
        /**
         * Filed from $_FILES
         */
        if (is_array($value)) {
            $this->setFileValue($value);
        }
        /**
         * Filed from $_POST.
         * May be not set. Need for clear value
         */
        if ($this->canClear() && is_string($value) && $value == $this->getClearValue()) {
            $this->setClearValue();
        }
        return $this;
    }

    public function setFileValue($value)
    {
        if (is_array($value) && UploadFileValidator::checkUploadSuccessCode($value)) {
            $this->_value = new UploadedFile($value);
        }
    }

    public function setClearValue()
    {
        $this->_value = null;
    }

    public function getClearValue()
    {
        return 'clear';
    }

    public function canClear()
    {
        $canClear = true;

        if ($this->getIsRequired()) {
            $canClear = false;
        }

        if ($this->getForm() instanceof ModelForm) {
            /** @var ModelForm $form */
            $form = $this->getForm();
            if ($form->getInstance()->getIsNew() || $this->getValue() == null) {
                $canClear = false;
            }
        }
        return $canClear;
    }

    public function getHtmlAccept()
    {
        if (!empty($this->accept)) {
            return implode(',', $this->accept);
        } else {
            return '*/*';
        }
    }

    /**
     * @return string current file url
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    public function getCurrentFileUrl()
    {
        /** @var StorageFile $value */
        $value = $this->getValue();
        if ($value instanceof StorageFile) {
            /** @var StorageManager $storageManager */
            $storageManager = Phact::app()->storage;
            /** @var Storage $storage */
            $storage = $storageManager->getStorage($value->storage);
            return $storage->getUrl($value->getPath());
        }

        return null;
    }


    public function getCurrentFileName()
    {
        /** @var StorageFile $value */
        $value = $this->getValue();
        if ($value instanceof StorageFile) {
            return basename($value->getPath());
        }
        return null;
    }
}