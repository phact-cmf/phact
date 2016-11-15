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
use Phact\Orm\Fields\Field;

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
    protected $_oldAttributes = [];

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

    /**
     * @return FieldsManager
     */
    public static function getFieldsManager()
    {
        $metaData = static::getMetaData();
        $fieldsManagerClass = FieldsManager::class;
        if (isset($metaData['fieldsManager'])) {
            $fieldsManagerClass = $metaData['fieldsManager'];
            unset($metaData['fieldsManager']);
        }
        $class = get_called_class();
        if (!$fieldsManagerClass::hasInstance($class)) {
            $fieldsManagerClass::makeInstance($class, static::getFields(), $metaData);
        }
        return $fieldsManagerClass::getInstance($class);
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

    /**
     * @return Field[]
     */
    public function getInitFields()
    {
        return $this->getFieldsManager()->getFields();
    }

    public function getPk()
    {
        $pkAttribute = $this->getPkAttribute();
        return isset($this->_attributes[$pkAttribute]) ? $this->_attributes[$pkAttribute] : null;
    }

    public function getField($name)
    {
        $manager = $this->getFieldsManager();
        if ($name == 'pk') {
            $name = $this->getPkAttribute();
        }
        if ($manager->has($name)) {
            $field = $manager->getField($name);
            $field->setModel($this);

            $attributeName = $field->getAttributeName();
            if ($attributeName) {
                $field->setAttribute($this->getAttribute($attributeName));
                $field->setOldAttribute($this->getOldAttribute($attributeName));
            }
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
        return null;
    }

    public function getOldAttribute($name)
    {
        if (array_key_exists($name, $this->_oldAttributes)) {
            return $this->_oldAttributes[$name];
        }
        return null;
    }

    protected function _setOldAttribute($name, $attribute)
    {
        $this->_oldAttributes[$name] = $attribute;
    }


    public function getValues()
    {
        $attributes = [];
        $field = $this->getFieldsList();
        foreach ($field as $fieldName) {
            $attributes[$fieldName] = $this->getFieldValue($fieldName);
        }
        return $attributes;
    }

    public function getAttributes()
    {
        return $this->_attributes;
    }

    public function hasAttribute($name)
    {
        return array_key_exists($name, $this->_attributes);
    }

    /**
     * Use setAttribute to safe set attribute value
     * @param $attributeName
     * @param $attribute
     */
    protected function _setAttribute($attributeName, $attribute)
    {
        $this->_attributes[$attributeName] = $attribute;
    }

    /**
     * Safe set attribute value
     *
     * @param $attributeName
     * @param $attribute
     */
    public function setAttribute($attributeName, $attribute)
    {

        $this->setFieldValue($attributeName, $attribute);

    }

    public function setAttributes($attributes)
    {
        if (!is_array($attributes)) {
            throw new InvalidArgumentException('Attributes should be an array');
        }
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }
    }

    public function setDbData($data)
    {
        foreach ($data as $name => $value) {
            $field = $this->getField($name);
            if ($field) {
                $field->setFromDbValue($value);
                $attributeName = $field->getAttributeName();

                if ($attributeName) {
                    $attribute = $field->getAttribute();
                    $oldAttribute = $field->getOldAttribute();

                    $this->_setAttribute($attributeName, $attribute);
                    $this->_setOldAttribute($attributeName, $oldAttribute);
                }
            }
        }
    }

    protected function _mergeOldAttributes($attributes)
    {
        foreach ($attributes as $name => $attribute) {
            $this->_oldAttributes[$name] = $attribute;
        }
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

    public function getFieldValue($field)
    {
        $manager = $this->getFieldsManager();
        if ($manager->has($field)) {
            return $manager->getFieldValue($this, $field);
        }
        return null;
    }

    public function setFieldValue($field, $value)
    {
        $manager = $this->getFieldsManager();
        if ($manager->has($field)) {

            $attributeName = $manager->getFieldAttributeName($field);

            $attribute = $manager->setFieldValue($this, $field, $value);

            if ($attributeName) {
                $this->_setAttribute($attributeName, $attribute);
            }
        }
    }

    public function __get($name)
    {
        $manager = $this->getFieldsManager();
        if ($manager->has($name)) {
            return $this->getFieldValue($name);
        } else {
            return $this->__smartGet($name);
        }
    }

    public function __set($name, $value)
    {
        $manager = $this->getFieldsManager();
        if ($manager->has($name)) {
            $this->setFieldValue($name, $value);
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
        $pk = $this->getPk();
        return !$pk;
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
            $field->setOldAttribute($this->getOldAttribute($attributeName));
            $field->{$eventName}();
            if ($metaEvent) {
                $field->{$metaEvent}();
            }
            if ($attributeName) {
                $this->_setAttribute($attributeName, $field->getAttribute());
                $this->_setOldAttribute($attributeName, $field->getOldAttribute());
            }

        }

        if(method_exists($this, $metaEvent)){
            $this->{$metaEvent}();
        }
    }

    public function getChangedAttributes($fields = [])
    {
        $changed = [];
        $fieldsManager = $this->getFieldsManager();
        if (!$fields) {
            $fields = $fieldsManager->getFieldsList();
        }
        foreach ($fields as $name) {
            $field = $this->getField($name);
            if ($field && ($attributeName = $field->getAttributeName()) && $field->getIsChanged()) {
                $changed[$attributeName] = $field->getAttribute();
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
            if (!$field->virtual) {
                $field->setModel($this);
                $field->setAttribute($value);
                $prepared[$attribute] = $field->getDbPreparedValue();
            }
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

        $this->_setAttribute($pkAttribute, $pk);
        $this->_provideEvent('afterInsert');
        $this->_mergeOldAttributes($data);

        return $pk;
    }

    public function update($fields = [])
    {
        $this->_provideEvent('beforeUpdate');
        $data = $this->getChangedAttributes($fields);
        $prepared = $this->getDbPreparedAttributes($data);

        if ($prepared == []) {
            $this->_provideEvent('afterUpdate');
            return true;
        }

        $query = $this->getQuery();
        $result = $query->updateByPk($this->getTableName(), $this->getPkAttribute(), $this->getPk(), $prepared);

        $this->_provideEvent('afterUpdate');
        $this->_mergeOldAttributes($data);

        return $result;
    }

    public function delete()
    {
        $this->_provideEvent('beforeDelete');
        $query = $this->getQuery();
        $result = $query->delete($this->getTableName(), $this->getPkAttribute(), $this->getPk());
        $this->_provideEvent('afterDelete');
        return $result;
    }

    public function __toString()
    {
        return (string) static::classNameShort();
    }

    public function beforeSave()
    {

    }

    public function afterSave()
    {

    }
}