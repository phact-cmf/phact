<?php

namespace Phact\Storage\Files;

/**
 * Class File
 * @package Mindy\Storage
 */
abstract class File implements FileInterface
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $size;
    /**
     * @var string
     */
    public $path;
    /**
     * @var string
     */
    public $type;
    /**
     * @var string|int
     */
    public $error = UPLOAD_ERR_OK;
    /**
     * @var string
     */
    protected $ext;

    /**
     * @return string
     */
    public function getExt()
    {
        if ($this->ext === null) {
            $this->ext = pathinfo($this->path, PATHINFO_EXTENSION);
            if (strlen($this->ext) != 0 && strpos($this->ext, '.') === 0) {
                unset($this->ext[0]);
            }
        }
        return $this->ext;
    }

    public function getContent()
    {
        return file_get_contents($this->path);
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getSize()
    {
        return $this->size;
    }
    
    public function getName()
    {
        return $this->name;
    }
}
