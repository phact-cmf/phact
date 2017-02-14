<?php

namespace Phact\Storage\Files;

/**
 * Class UploadedFile
 * @package Mindy\Storage
 */
class UploadedFile extends File
{
    public function __construct(array $data)
    {
        $this->name = basename($data['name']);
        $this->path = $data['tmp_name'];
        $this->size = $data['size'];
        $this->type = $data['type'];
        $this->error = $data['error'];
    }

    public function getExt()
    {
        if ($this->ext === null) {
            $this->ext = pathinfo($this->name, PATHINFO_EXTENSION);
            if (strlen($this->ext) != 0 && strpos($this->ext, '.') === 0) {
                unset($this->ext[0]);
            }
        }
        return $this->ext;
    }

    /**
     * @return string base file name
     */
    public function getName()
    {
        return $this->name;
    }
}
