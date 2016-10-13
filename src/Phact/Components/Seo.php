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
 * @date 04/10/16 14:50
 */

namespace Phact\Components;

use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;

class Seo
{
    use SmartProperties;

    protected $_title = null;

    protected $_description = null;

    protected $_keywords = null;

    public function setTitle($title)
    {
        $this->_title = $title;
    }

    public function getTitle()
    {
        return $this->_title;
    }

    public function setDescription($description)
    {
        $this->_description = $description;
    }

    public function getDescription()
    {
        return $this->_description;
    }

    public function setKeywords($description)
    {
        $this->_description = $description;
    }

    public function getKeywords()
    {
        return $this->_description;
    }
}