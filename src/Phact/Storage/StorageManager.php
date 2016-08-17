<?php
/**
 * Created by PhpStorm.
 * User: aleksandrgordeev
 * Date: 08.08.16
 * Time: 13:25
 */

namespace Phact\Storage;



use Phact\Exceptions\InvalidConfigException;
use Phact\Helpers\Configurator;

class StorageManager
{
    public $config = [];

    private $_storage;


    public function getStorage($name = null)
    {
        $config = $this->config;
        $name = ($name === null) ? 'default' : $name;
        if (!$this->_storage) {
            if (!isset($config[$name])){
                throw new InvalidConfigException("Storage with name $name not configure");
            }
            $this->_storage = Configurator::create(array_merge($config[$name], ['name'=>$name]));
        }
        return $this->_storage;
    }


    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->getStorage(), $name], $arguments);
    }

    public function __get($name)
    {
        return $this->getStorage()->{$name};
    }

    public function __set($name, $value)
    {
        $this->getStorage()->{$name} = $value;
    }

}