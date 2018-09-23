<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 04/10/16 14:50
 */

namespace Phact\Components;

use Exception;
use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;

class Meta implements MetaInterface
{
    use SmartProperties;

    protected $_title = null;

    protected $_description = null;

    protected $_keywords = null;

    protected $_canonical;

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

    public function setKeywords($keywords)
    {
        $this->_keywords = $keywords;
    }

    public function getKeywords()
    {
        return $this->_keywords;
    }

    public function getCanonical()
    {
        return $this->_canonical;
    }

    public function setCanonical($canonical)
    {
        $this->_canonical = $canonical;
    }

    public function getData()
    {
        $data = [];
        foreach (['title', 'description', 'keywords', 'canonical'] as $name) {
            $data[$name] = $this->{$name};
        }
        return $data;
    }

    public function useTemplate($key, $params = [])
    {
        throw new Exception("Not implemented");
    }
}