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

use InvalidArgumentException;
use Phact\Exceptions\UnknownMethodException;
use Phact\Helpers\ClassNames;
use Phact\Helpers\SmartProperties;
use Phact\Helpers\Text;
use Phact\Main\Phact;

/**
 * Class Model
 * 
 * @method static Manager objects($model = null)
 * 
 * @package Phact\Orm
 */
class Model
{
    use SmartProperties, ClassNames;

    static $_fieldsManager;
    static $_query;

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
        $moduleName = static::getModuleName();
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
        static::$_fieldsManager = new $fieldsManager(get_called_class(), static::getFields(), $metaData);
    }

    /**
     * @return FieldsManager
     */
    public static function getFieldsManager()
    {
        return static::$_fieldsManager;
    }

    public function getFieldsList()
    {
        return $this->getFieldsManager()->getFieldsList();
    }

    public function getAttributesList()
    {
        return $this->getFieldsManager()->getAttributesList();
    }

    public function getPkField()
    {
        return $this->getFieldsManager()->getPkField();
    }

    public function getPkAttribute()
    {
        return $this->getFieldsManager()->getPkAttribute();
    }

    public function getPk()
    {
        $pkAttribute = $this->getPkAttribute();
        return isset($this->_dbAttributes[$pkAttribute]) ? $this->_dbAttributes[$pkAttribute] : null;
    }

    public function getField($name)
    {
        $manager = $this->getFieldsManager();
        if ($name == 'pk') {
            $name = $this->getPkAttribute();
        }
        if ($manager->has($name)) {
            $field = $manager->getField($name);
            $attributeName = $field->getAttributeName();
            $field->setModel($this);
            $field->setAttribute($this->getAttribute($attributeName));
            return $field;
        }
        return null;
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
            'fieldsManager' => FieldsManager::class
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
        if (array_key_exists($name, $this->_attributes)) {
            return $this->_attributes[$name];
        }
        if (array_key_exists($name, $this->_dbAttributes)) {
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
        $this->_dbAttributes = $attributes;
    }

    public function setOldAttributes($attributes)
    {
        $this->_oldAttributes = $attributes;
    }

    public function clearAttributes()
    {
        $this->_attributes = [];
    }

    /**
     * @return Query
     */
    public static function getQuery()
    {
        if (!static::$_query) {
            static::$_query = new Query(static::getConnectionName());
        }
        return static::$_query;
    }
    
    public static function getConnectionName()
    {
        $metaData = static::getMetaData();
        return isset($metaData['connection']) ? $metaData['connection'] : 'default';
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

    public function getIsNew()
    {
        return empty($this->_dbAttributes);
    }

    public function save($fields = [])
    {
        if ($this->getIsNew()) {
            return $this->insert($fields);
        } else {
            return $this->update($fields);
        }
    }

    protected function _beforeInsert()
    {

    }

    protected function _provideEvent($eventName)
    {
        $events = ['beforeInsert', 'afterInsert', 'beforeUpdate', 'afterUpdate', 'beforeDelete', 'afterDelete'];
        if (!in_array($eventName, $events)) {
            throw new InvalidArgumentException("Invalid event name. Event name must be one of this: " . implode(', ', $events));
        }
        $metaEvent = null;
        if (in_array($eventName, ['beforeInsert', 'beforeUpdate'])) {
            $metaEvent = 'beforeSave';
        } elseif (in_array($eventName, ['afterInsert', 'afterUpdate'])) {
            $metaEvent = 'afterSave';
        }
        $fields = $this->getFieldsManager()->getFields();
        foreach ($fields as $name => $field) {
            $attributeName = $field->getAttributeName();
            $field->setModel($this);
            $field->setAttribute($this->getAttribute($attributeName));
            $field->{$eventName}();
            if ($metaEvent) {
                $field->{$metaEvent}();
            }
        }
    }

    public function getChangedAttributes($fields = [])
    {
        $fieldsManager = $this->getFieldsManager();
        if (!$fields) {
            $fields = $fieldsManager->getFieldsList();
        }
        $changed = [];
        $attributes = $fieldsManager->getAttributesListByFields($fields);
        foreach ($attributes as $attributeName) {
            if (array_key_exists($attributeName, $this->_attributes)) {
                $value = $this->_attributes[$attributeName];
                if (!array_key_exists($attributeName, $this->_dbAttributes) || $this->_dbAttributes[$attributeName] != $value) {
                    $changed[$attributeName] = $this->_attributes[$attributeName];
                }
            }
        }
        return $changed;
    }

    public function getDbPreparedAttributes($attributes = [])
    {
        $prepared = [];
        $fieldsManager = $this->getFieldsManager();
        foreach ($attributes as $attribute => $value) {
            $field = $fieldsManager->getFieldByAttribute($attribute);
            $field->setModel($this);
            $field->setAttribute($value);
            $prepared[$attribute] = $field->getDbPreparedValue();
        }
        return $prepared;
    }

    public function insert($fields = [])
    {
        $this->_provideEvent('beforeInsert');
        $data = $this->getChangedAttributes($fields);
        $prepared = $this->getDbPreparedAttributes($data);

        $query = $this->getQuery();

        $pk = $query->insert($this->getTableName(), $prepared);
        $pkAttribute = $this->getPkAttribute();

        $prepared[$pkAttribute] = $pk;

        $this->clearAttributes();
        $this->setDbAttributes($prepared);
        $this->_provideEvent('afterInsert');
        $this->setOldAttributes($prepared);

        return true;
    }

    public function update($fields = [])
    {
        $this->_provideEvent('beforeUpdate');
        $data = $this->getChangedAttributes($fields);
        $prepared = $this->getDbPreparedAttributes($data);

        $query = $this->getQuery();
        $query->updateByPk($this->getTableName(), $this->getPkAttribute(), $this->getPk(), $prepared);

        $this->clearAttributes();
        $this->setDbAttributes($prepared);
        $this->_provideEvent('afterUpdate');
        $this->setOldAttributes($prepared);

        return true;
    }
}