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
 * @date 13/04/16 08:11
 */

namespace Phact\Orm\Fields;

use Phact\Exceptions\InvalidAttributeException;
use Phact\Main\Phact;
use Phact\Storage\Files\StorageFile;
use Phact\Storage\Files\File;
use Phact\Storage\Files\FileInterface;
use Phact\Storage\Files\LocalFile;
use Phact\Storage\Storage;
use Phact\Storage\StorageManager;

class FileField extends CharField
{
    /**
     * @var bool, encrypt filename to md5 hash
     */
    public $md5Name = false;

    /**
     * %Module - Module object class of model field. For example: Catalog
     * %Model - Model object class. For example: Product
     * %Y  Year on server, for example 2016
     * %m  month on server, for example 05
     * %d  day on server, for example 03
     * %H  hour, example 11
     * %i  minutes, example 01
     * %s  sec, example 11
     * @var string/function, upload template directory:
     */
    public $templateUploadDir = '%Module/%Model/%Y-%m-%d';

    /** @var null|string storage type. Default FileSystemStorage */
    public $storage = null;

    /** @var Storage */
    protected $_storage;

    /** @var  string upload directory for field */
    protected $_uploadDir;


    /**
     * @return Storage
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    public function getStorage()
    {
        if (!$this->_storage) {
            /** @var StorageManager $component */
            $component = Phact::app()->storage;
            $this->_storage = $component->getStorage($this->storage);
        }
        return $this->_storage;
    }


    /**
     * @return string|null
     */
    public function getUrl()
    {
        if (is_a($this->attribute, FileInterface::class)) {
            return $this->getStorage()->getUrl($this->attribute->path);
        }
        return null;
    }

    /**
     * @return string|null full path to file
     */
    public function getPath()
    {
        if (is_a($this->attribute, FileInterface::class)) {
            return $this->getStorage()->getPath($this->attribute->path);
        }
        return null;
    }


    /**
     * @return string extension of file
     */
    public function getExtension()
    {
        if (is_a($this->attribute, FileInterface::class)) {
            return $this->getStorage()->getExtension($this->attribute->getPath());
        }
        return null;
    }

    /**
     * @return string extension of file
     */
    public function getSize()
    {
        if (is_a($this->attribute, FileInterface::class)) {
            return $this->getStorage()->getSize($this->attribute->getPath());
        }
        return null;
    }

    /**
     * @return null|bool if success delete
     */
    public function delete()
    {
        if (is_a($this->attribute, FileInterface::class)) {
            return $this->getStorage()->delete($this->attribute->getPath());
        }
        return null;
    }

    /**
     * Directory for upload related to storage
     *
     * @return mixed|null|string
     */
    public function getUploadDir()
    {
        if (is_null($this->_uploadDir)) {
            if (is_callable($this->templateUploadDir)) {
                $result = call_user_func($this->templateUploadDir, $this);
                if (is_string($result)) {
                    return $result;
                }
            }

            $uploadTo = strtr($this->templateUploadDir, [
                '%Y' => date('Y'),
                '%m' => date('m'),
                '%d' => date('d'),
                '%H' => date('H'),
                '%i' => date('i'),
                '%s' => date('s'),
                '%Model' => $this->getModel()->classNameShort(),
                '%Module' => $this->getModel()->getModuleName(),
            ]);

            $this->_uploadDir = rtrim($uploadTo);

            if ($this->_uploadDir) {
                $this->_uploadDir .= DIRECTORY_SEPARATOR;
            }
        }
        return $this->_uploadDir ?: null;
    }

    /**
     * Delete old file
     */
    public function deleteOld()
    {
        /** @var FileInterface|null $old */
        $old = $this->getOldAttribute();
        if (is_a($old, FileInterface::class)) {
            $path = $old->getPath();
            $this->getStorage()->delete($path);
        }
    }

    /**
     * @param $value string db value
     * @return null|StorageFile
     */
    protected function attributePrepareValue($value)
    {
        if (!is_null($value)) {
            $value = new StorageFile($value, $this->storage);
        }
        return $value;
    }

    /**
     * @param File|string|null $value
     * @param null $aliasConfig
     * @return mixed|void
     * @throws InvalidAttributeException
     */
    public function setValue($value, $aliasConfig = NULL)
    {
        if (is_null($value)) {
            $this->attribute = null;
        }

        if ($value instanceof StorageFile) {
            if (!$value->equalsTo($this->attribute)) {
                $this->attribute = $this->saveStorageFile($value);
            }
        }

        if (is_string($value) && file_exists($value) && is_readable($value)) {
            $value = new LocalFile($value);
        }


        if ($value instanceof File) {
            $this->attribute = $this->saveFile($value);
        }
        
        return $this->attribute;

    }

    /**
     * Prepare attribute for database
     *
     * @param $value StorageFile|null
     * @return string
     */
    public function dbPrepareValue($value)
    {
        if ($value instanceof StorageFile) {
            return $value->path;
        } else {
            return $value;
        }
    }

    /**
     * Prepare name
     *
     * @param FileInterface $file
     * @return string
     */
    public function getFileName(FileInterface $file)
    {
        $name = $file->getName();
        if ($this->md5Name) {
            $name = md5($name . uniqid()) . '.' . $file->getExt();
        }

        return $name;
    }

    /**
     * @param null $aliasConfig
     * @return mixed
     */
    public function getValue($aliasConfig = NULL)
    {
        return $this;
    }

    /**
     * @param File $file
     * @return StorageFile|false
     */
    protected function saveFile(File $file)
    {
        $uploadDir = $this->getUploadDir();
        $name = $this->getFileName($file);
        $path = $this->getStorage()->save($uploadDir . $name, $file->getContent());
        return ($path) ? new StorageFile($path, $this->storage) : false;
    }

    /**
     * @param StorageFile $file
     * @return StorageFile|false
     */
    protected function saveStorageFile(StorageFile $file)
    {
        $uploadDir = $this->getUploadDir();
        $name = $this->getFileName($file);
        /** @var StorageFile $file */
        $fileStorage = $this->getStorage()->copyStorageFile($uploadDir . $name, $file);
        return $fileStorage;

    }

    public function afterDelete()
    {
        $this->deleteOld();
        parent::afterDelete();
    }

    public function afterSave()
    {
        if (is_null($this->attribute)) {
            $this->deleteOld();
        } elseif ($this->oldAttribute instanceof StorageFile && !$this->oldAttribute->equalsTo($this->attribute)) {
            $this->deleteOld();
        }

        parent::afterSave();
    }

    public function getFormField()
    {
        return $this->setUpFormField([
            'class' => \Phact\Form\Fields\FileField::class
        ]);
    }
}