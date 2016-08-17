<?php

/**
 * User: max
 * Date: 24/07/15
 * Time: 16:23
 */

namespace Phact\Storage\Files;

class ResourceFile extends File
{
    private $_content = '';

    public function __construct($content, $name = null, $size = null, $type = null)
    {
        $this->path = '';
        $this->name = $name;
        $this->size = $size;
        $this->type = $type;

        $this->_content = $content;
    }

    public function getContent()
    {
        return $this->_content;
    }
}
