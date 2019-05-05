<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 12/04/16 18:50
 */

namespace Phact\Orm;

use InvalidArgumentException;
use Phact\Event\EventManager;
use Phact\Event\EventManagerInterface;
use Phact\Exceptions\UnknownMethodException;
use Phact\Helpers\ClassNames;
use Phact\Helpers\SmartProperties;
use Phact\Helpers\Text;
use Phact\Main\Phact;
use Phact\Orm\Configuration\ConfigurationProvider;
use Phact\Orm\Fields\Field;
use Phact\Orm\Fields\RelationField;
use Serializable;

/**
 * Class Model
 *
 * @method static Manager objects($model = null)
 *
 * @package Phact\Orm
 */
class Model implements Serializable
{
    use SmartProperties, ClassNames;

    static $_fieldsManagers = [];
    static $_queries = [];
    static $_tableNames = [];
    static $_eventManager;

    protected $_attributes = [];
    protected $_oldAttributes = [];
    protected $_withModels = [];

    protected $_isNew = false;

    public function __construct($attributes = [])
    {
        if (!empty($attributes)) {
            $this->setAttributes($attributes);
        }
    }

    /**
     * @return null|EventManagerInterface
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    public static function getEventManager(): ?EventManagerInterface
    {
        $configuration = ConfigurationProvider::getInstance()->getManager();
        $eventManager = $configuration->getEventManager();
        if (!self::$_eventManager) {
            self::$_eventManager = $eventManager;
        }
        return self::$_eventManager;
    }

    public static function getTableName()
    {
        $class = static::class;
        if (!isset(self::$_tableNames[$class])) {
            $classParts = explode('\\', $class);
            $name = array_pop($classParts);
            $moduleName = static::getModuleName();
            if ($moduleName) {
                $name = $moduleName . $name;
            }
            self::$_tableNames[$class] = Text::camelCaseToUnderscores($name);
        }
        return self::$_tableNames[$class];
    }

    /**
     * @return FieldsManager
     */
    public static function getFieldsManager()
    {
        /** @var FieldsManager $fieldsManagerClass */
        $class = static::class;
        if (!isset(self::$_fieldsManagers[$class])) {
            $fieldsManagerClass = static::getFieldsManagerClass();
            self::$_fieldsManagers[$class] = $fieldsManagerClass::makeInstance($class, static::getFields(), static::getMetaData());
        }
        return self::$_fieldsManagers[$class];
    }

    public static function getFieldsManagerClass()
    {
        return FieldsManager::class;
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
        $fields = [];
        $names = $this->getFieldsManager()->getFieldsList();
        foreach ($names as $name) {
            $fields[$name] = $this->getField($name);
        }
        return $fields;
    }

    public function getPk()
    {
        $pkAttribute = $this->getPkAttribute();
        return isset($this->_attributes[$pkAttribute]) ? $this->_attributes[$pkAttribute] : null;
    }

    public function fetchField($name)
    {
        if ($name == 'pk') {
            $name = $this->getPkAttribute();
        }
        return $this->getFieldsManager()->getField($name);
    }

    public function getField($name)
    {
        if ($field = $this->fetchField($name)) {
            $field->clean();
            $field->setModel($this);
            if ($attributeName = $field->getAttributeName()) {
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
            $model = new static;
        }
        return new Manager($model);
    }

    public function getAttribute($name)
    {
        if (isset($this->_attributes[$name])) {
            return $this->_attributes[$name];
        }
        return null;
    }

    public function getOldAttribute($name)
    {
        if (isset($this->_oldAttributes[$name])) {
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
        return isset($this->_attributes[$name]);
    }

    public function hasField($name)
    {
        return $this->getFieldsManager()->hasField($name);
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
        $manager = $this->getFieldsManager();
        $withData = [];
        foreach ($data as $name => $value) {
            if ($field = $manager->getField($name)) {
                $attributeName = $field->getAttributeName();
                if ($field->rawSet) {
                    $attribute = $field->attributePrepareValue($value);
                    if ($attributeName) {
                        $this->_attributes[$attributeName] = $attribute;
                        $this->_oldAttributes[$attributeName] = $attribute;
                    }
                } else {
                    $field->setModel($this);
                    $field->setFromDbValue($value);

                    if ($attributeName) {
                        $this->_attributes[$attributeName] = $field->getAttribute();
                        $this->_oldAttributes[$attributeName] = $field->getOldAttribute();
                    }
                }
            } else {
                $withData[$name] = $value;
            }
        }

        if ($withData) {
            $this->_withModels = $withData;
        }
    }

    public function getWithData($name)
    {
        return isset($this->_withModels[$name]) ? $this->_withModels[$name] : null;
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
        $class = static::class;
        if (!isset(self::$_queries[$class])) {
            self::$_queries[$class] = new Query(static::getConnectionName());
        }
        return self::$_queries[$class];
    }

    public static function getConnectionName()
    {
        $metaData = static::getMetaData();
        return isset($metaData['connection']) ? $metaData['connection'] : 'default';
    }

    public function getFieldValue($field)
    {
        return $this->getFieldsManager()->getFieldValue($this, $field);
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

    public function getIsChoiceValue($value)
    {
        $data = explode('_', Text::camelCaseToUnderscores($value));
        $field = array_shift($data);
        $name = implode('_', $data);
        $cName = static::class . '::' . mb_strtoupper($field . '_' . $name);
        if ($this->hasField($field) && defined($cName)) {
            $value = $this->getFieldValue($field);
            return $value == constant($cName);
        }
        return null;
    }

    public function __get($name)
    {
        $manager = $this->getFieldsManager();
        if ($manager->has($name)) {
            return $this->getFieldValue($name);
        } else {
            if (substr($name, -9) == '__display') {
                $start = mb_strpos($name, '__display', 0, 'UTF-8');
                $name = mb_substr($name, 0, $start, 'UTF-8');

                if ($manager->has($name) && ($field = $this->getField($name))) {
                    return $field->getChoiceDisplay();
                }
            }
            if (substr($name, 0, 2) == 'is') {
                $value = $this->getIsChoiceValue(mb_substr($name, 2, null, 'UTF-8'));
                if (!is_null($value)) {
                    return $value;
                }
            }
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

    public function __isset($name)
    {
        $manager = $this->getFieldsManager();
        return $manager->has($name);
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
        if ($this->_isNew) {
            return true;
        }
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

        $event = self::getEventManager();
        $this->{$eventName}();
        if ($event) {
            $event->trigger(self::class . '::' . $eventName, [], $this);
            $event->trigger('model.' . $eventName, [], $this);
        }
        if(method_exists($this, $metaEvent)){
            $this->{$metaEvent}();
            if ($event) {
                $event->trigger(self::class . '::' . $metaEvent, [], $this);
                $event->trigger('model.' . $eventName, [], $this);
            }
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

    public function getChangedAttribute($attribute)
    {
        $attributes = $this->getChangedAttributes();
        return isset($attributes[$attribute]) ? $attributes[$attribute] : null;
    }

    public function getIsChangedAttribute($attribute)
    {
        $attributes = $this->getChangedAttributes();
        return array_key_exists($attribute, $attributes) ? true : false;
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

    public function getDbPreparedAttribute($attribute)
    {
        $attributes = $this->getDbPreparedAttributes();
        return isset($attributes[$attribute]) ? $attributes[$attribute] : null;
    }

    public function insert($fields = [])
    {
        $this->_isNew = true;
        $this->_provideEvent('beforeInsert');
        $data = $this->getChangedAttributes($fields);
        $prepared = $this->getDbPreparedAttributes($data);

        $query = $this->getQuery();
        $pk = $query->insert($this->getTableName(), $prepared);
        $pkAttribute = $this->getPkAttribute();
        
        $field = $this->getFieldsManager()->getField($pkAttribute);
        $this->_attributes[$pkAttribute] = $field->attributePrepareValue($pk);

        $this->_provideEvent('afterInsert');
        $this->_mergeOldAttributes($data);
        $this->_isNew = false;
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
        $result = $query->update($this->getTableName(), [$this->getPkAttribute() => $this->getPk()], $prepared);

        $this->_provideEvent('afterUpdate');
        $this->_mergeOldAttributes($data);

        return $result;
    }

    public function delete()
    {
        $this->_provideEvent('beforeDelete');
        $query = $this->getQuery();
        $result = $query->delete($this->getTableName(), [$this->getPkAttribute() => $this->getPk()]);
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

    public function beforeUpdate()
    {

    }

    public function afterUpdate()
    {

    }

    public function beforeInsert()
    {

    }

    public function afterInsert()
    {

    }

    public function beforeDelete()
    {

    }

    public function afterDelete()
    {

    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        $data = $this->getDbPreparedAttributes($this->getAttributes());
        return serialize($data);
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->setDbData($data);
    }
}