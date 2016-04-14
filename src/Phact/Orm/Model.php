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
 * @date 12/04/16 18:50
 */

namespace Phact\Orm;

use Phact\Exceptions\UnknownMethodException;
use Phact\Helpers\SmartProperties;
use Phact\Helpers\Text;

/**
 * Class Model
 * 
 * @method static Manager objects($model = null)
 * 
 * @package Phact\Orm
 */
class Model
{
    use SmartProperties;

    static $_fieldsManager;

    protected $_attributes = [];
    protected $_dbAttributes = [];
    protected $_oldAttributes = [];

    public function __construct()
    {
        static::setFieldsManager();
    }

    public static function getTableName()
    {
        $class = get_called_class();
        $classParts = explode('\\', $class);
        $name = array_pop($classParts);
        $moduleName = self::getModuleName();
        $tableName = Text::camelCaseToUnderscores($name);
        if ($moduleName) {
            $tableName = Text::camelCaseToUnderscores($moduleName) . '_' . $tableName;
        }
        return $tableName;
    }

    public static function getModuleName()
    {
        $class = get_called_class();
        $classParts = explode('\\', $class);
        if ($classParts[0] == 'Modules' && isset($classParts[1])) {
            return $classParts[1];
        }
        return null;
    }

    public static function setFieldsManager()
    {
        $metaData = static::getMetaData();
        $fieldsManager = FieldsManager::class;
        if (isset($metaData['fieldsManager'])) {
            $fieldsManager = $metaData['fieldsManager'];
            unset($metaData['fieldsManager']);
        }
        static::$_fieldsManager = new $fieldsManager(static::getFields(), $metaData);
    }

    /**
     * @return FieldsManager
     */
    public static function getFieldsManager()
    {
        return static::$_fieldsManager;
    }

    /**
     * @return array
     */
    public static function getFields()
    {
        return [];
    }

    public static function getMetaData()
    {
        return [
            'fieldsManager' => FieldsManager::class,
            'tableName' => static::getTableName()
        ];
    }

    public static function objectsManager($model = null)
    {
        if (!$model) {
            $class = get_called_class();
            $model = new $class();
        }
        return new Manager($model);
    }

    public function getAttribute($name)
    {
        if (isset($this->_attributes[$name])) {
            return $this->_attributes[$name];
        }
        if (isset($this->_dbAttributes[$name])) {
            return $this->_dbAttributes[$name];
        }
        return null;
    }

    public function setAttribute($attributeName, $attribute)
    {
        $this->_attributes[$attributeName] = $attribute;
    }

    public function setDbData($data)
    {
        $this->setDbAttributes($data);
        $this->setOldAttributes($data);
    }

    public function setDbAttributes($attributes)
    {
        return $this->_dbAttributes = $attributes;
    }

    public function setOldAttributes($attributes)
    {
        return $this->_oldAttributes = $attributes;
    }

    public static function className()
    {
        return get_called_class();
    }

    public function __get($name)
    {
        $manager = $this->getFieldsManager();
        if ($manager->has($name)) {
            $attributeName = $manager->getFieldAttributeName($name);
            $attribute = null;
            if ($attributeName) {
                $attribute = $this->getAttribute($attributeName);
            }
            return $manager->getFieldValue($name, $attribute);
        } else {
            return $this->__smartGet($name);
        }
    }

    public function __set($name, $value)
    {
        $manager = $this->getFieldsManager();
        if ($manager->has($name)) {
            $attributeName = $manager->getFieldAttributeName($name);
            $attribute = $manager->setFieldValue($name, $value);
            if ($attributeName) {
                $this->setAttribute($attributeName, $attribute);
            }
        } else {
            $this->__smartSet($name, $value);
        }
    }

    public static function __callStatic($method, $args)
    {
        $managerMethod = $method . 'Manager';
        $className = get_called_class();
        if (method_exists($className, $managerMethod) && is_callable([$className, $managerMethod])) {
            return call_user_func_array([$className, $managerMethod], $args);
        } else {
            throw new UnknownMethodException("Call unknown method {$method}");
        }
    }

    public function __call($method, $args)
    {
        $managerMethod = $method . 'Manager';
        if (method_exists($this, $managerMethod)) {
            return call_user_func_array([$this, $managerMethod], array_merge([$this], $args));
        } else {
            throw new UnknownMethodException("Call unknown method {$method}");
        }
    }

    public function save($fields)
    {
        
    }
}