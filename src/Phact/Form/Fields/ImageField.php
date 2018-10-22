<?php
/**
 * Created by PhpStorm.
 * User: aleksandrgordeev
 * Date: 11.08.16
 * Time: 20:43
 */

namespace Phact\Form\Fields;


use Phact\Main\Phact;
use Phact\Storage\Files\StorageFile;
use Phact\Storage\StorageInterface;
use Phact\Validators\ImageValidator;

class ImageField extends FileField
{

    public $inputTemplate = 'forms/field/image/input.tpl';

    public $sizeShowValue = null;
    public $accept = ['image/*'];

    public $storage;

    public function setDefaultValidators()
    {
        parent::setDefaultValidators();
        $this->_validators[] = new ImageValidator($this->accept, $this->maxSize);

    }

    public function getOriginalImage()
    {
        return $this->getCurrentFileUrl();
    }


    public function getSizeImage()
    {
        if(!$this->sizeShowValue){
            return $this->getOriginalImage();
        }

        $value = $this->getValue();
        if ($value instanceof StorageFile) {
            if ($this->sizeShowValue) {
                /** @var StorageInterface $storage */
                $directory = pathinfo($value->path, PATHINFO_DIRNAME);
                $file = pathinfo($value->getPath(), PATHINFO_BASENAME);

                $path = $directory . DIRECTORY_SEPARATOR . $this->sizeShowValue . '_' . $file;
            } else {
                $path = $value->getPath();
            }

            return Phact::app()->getComponent($value->storage)->getUrl($path);
        }
    }
}