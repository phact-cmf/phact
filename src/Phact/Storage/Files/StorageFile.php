<?php
/**
 * Created by PhpStorm.
 * User: aleksandrgordeev
 * Date: 10.08.16
 * Time: 14:09
 */

namespace Phact\Storage\Files;


use Phact\Helpers\FileHelper;
use Phact\Main\Phact;
use Phact\Storage\Storage;

class StorageFile implements FileInterface
{
    public $path;

    public $storage;

    protected $_storageSystem;

    public function __construct($path, $storage = null)
    {
        $this->path = $path;
        $this->storage = $storage ?? 'storage';
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string baseName
     */
    public function getBaseName()
    {
        return $this->getName();
    }


    /**
     * @return string|null file ext
     */
    public function getExt()
    {
        return $this->getStorageSystem()->getExtension($this->getPath());
    }

    /**
     * @return string base file name
     */
    public function getName()
    {
        return FileHelper::mbBasename($this->getPath());
    }

    /**
     * @return string file content
     */
    public function getContent()
    {
        return $this->getStorageSystem()->getContent($this->getPath());
    }

    /**
     * @return Storage
     */
    public function getStorageSystem()
    {
        if ($this->_storageSystem === null) {
            $this->_storageSystem = Phact::app()->getComponent($this->storage);
        }
        return $this->_storageSystem;
    }

    /**
     * @param $file null|self
     * @return bool
     */
    public function equalsTo($file)
    {
        if(!$file){
            return false;
        }

        if (($this->storage == $file->storage) && ($this->getPath() == $file->getPath())) {
            return true;
        }

        return false;
    }

    /**
     * @return int|null file size
     */
    public function getSize()
    {
        return $this->getStorageSystem()->getSize($this->getPath());
    }

    public function __toString()
    {
        return (string) $this->path;
    }
}