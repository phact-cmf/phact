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
 * @date 13/05/16 07:28
 */

namespace Phact\Orm;


use Phact\Orm\Fields\AutoField;
use Phact\Orm\Fields\Field;
use Phact\Orm\Fields\ForeignField;
use Phact\Orm\Fields\HasManyField;
use Phact\Orm\Fields\ManyToManyField;

class TableManager
{
    public $defaultEngine = 'InnoDB';
    public $defaultCharset = 'utf8';
    public $checkExists = true;

    const DROP_CASCADE = 1;
    const DROP_RESTRICT = 2;

    public $deleteMode = self::DROP_CASCADE;

    public function create($models = [])
    {
        foreach ($models as $model) {
            $this->createModelTable($model);
        }
        $this->createForeignKeys($models);
    }

    public function drop($models = [], $mode = self::DROP_CASCADE)
    {
        foreach ($models as $model) {
            $this->dropModelTable($model, $mode);
        }
    }

    /**
     * @param $model Model
     */
    public function createModelTable($model)
    {
        $engine = $this->defaultEngine;
        $charset = $this->defaultCharset;

        $exists = "";
        if ($this->checkExists) {
            $exists = "IF NOT EXISTS";
        }

        $tableName = $model->getTableName();
        $queryLayer = $model->objects()->getQuerySet()->getQueryLayer();
        $tableName = $queryLayer->getQueryBuilderRaw()->addTablePrefix($tableName);
        $tableNameSafe = $queryLayer->sanitize($tableName);

        $fieldsStatements = $this->makeFieldsStatements($model, $queryLayer);
        $fields = implode(',', $fieldsStatements);

        $query = "CREATE TABLE {$exists} {$tableNameSafe} ({$fields}) ENGINE={$engine} DEFAULT CHARSET={$charset}";
        list($result) = $queryLayer->getQueryBuilderRaw()->statement($query);
        $this->createM2MTables($model);
    }

    /**
     * @param $model Model
     */
    public function createM2MTables($model)
    {
        $engine = $this->defaultEngine;
        $charset = $this->defaultCharset;

        $queryLayer = $model->objects()->getQuerySet()->getQueryLayer();

        foreach ($model->getFieldsManager()->getFields() as $field) {
            if ($field instanceof ManyToManyField && !$field->getThrough()) {
                $tableName = $field->getThroughTableName();
                $tableNameSafe = $queryLayer->getQueryBuilderRaw()->addTablePrefix($tableName);
                $tableNameSafe = $queryLayer->sanitize($tableNameSafe);

                $statements = [];

                $toModelClass = $field->modelClass;
                /** @var Model $toModel */
                $toModel = new $toModelClass();

                $to = $field->getTo();
                $columnTo = $field->getThroughTo();
                $columnTo = $queryLayer->sanitize($columnTo);
                $toType = $toModel->getField($to)->getSqlType();
                $statements[] = "{$columnTo} {$toType} NOT NULL";

                $from = $field->getFrom();
                $columnFrom = $field->getThroughFrom();
                $columnFrom = $queryLayer->sanitize($columnFrom);
                $fromType = $model->getField($from)->getSqlType();
                $statements[] = "{$columnFrom} {$fromType} NOT NULL";

                $fields = implode(',', $statements);

                $query = "CREATE TABLE IF NOT EXISTS {$tableNameSafe} ({$fields}) ENGINE={$engine} DEFAULT CHARSET={$charset}";
                list($result) = $queryLayer->getQueryBuilderRaw()->statement($query);
            }
        }
    }

    /**
     * @param $model Model
     * @param $queryLayer QueryLayer
     * @return array
     */
    public function makeFieldsStatements($model, $queryLayer)
    {
        $fieldsManager = $model->getFieldsManager();
        $fields = $fieldsManager->getFields();
        $statements = [];
        /** @var Field $field */
        foreach ($fields as $field) {
            $attribute = $field->getAttributeName();
            if ($attribute) {
                $column = $queryLayer->sanitize($attribute);
                $type = $field->getSqlType();

                $statement = [$column, $type];
                $default = null;
                if (!$field->null) {
                    $statement[] = "NOT NULL";
                } else {
                    $default = "NULL";
                }

                if ($field->default) {
                    if (is_string($field->default)) {
                        $default = "'{$field->default}'";
                    } elseif ($field->default instanceof Expression) {
                        $default = $field->default->getExpression();
                    } elseif(is_numeric($field->default)) {
                        $default = $field->default;
                    }
                }

                if ($default) {
                    $statement[] = "DEFAULT $default";
                }

                if ($field instanceof AutoField) {
                    $statement[] = "AUTO_INCREMENT";
                }

                if ($field->pk) {
                    $statement[] = "PRIMARY KEY";
                }

                $statements[] = implode(' ', $statement);
            }
        }
        return $statements;
    }

    public function createForeignKeys($models)
    {
        foreach ($models as $model) {
            $this->createModelForeignKeys($model);
        }
    }

    /**
     * @param $model Model
     */
    public function createModelForeignKeys($model)
    {
        $tableName = $model->getTableName();
        $queryLayer = $model->objects()->getQuerySet()->getQueryLayer();
        $tableName = $queryLayer->getQueryBuilderRaw()->addTablePrefix($tableName);

        $fieldsManager = $model->getFieldsManager();
        $fields = $fieldsManager->getFields();

        foreach ($fields as $field) {
            if ($field instanceof ForeignField) {
                $relationClass = $field->modelClass;
                $relationTableName = $relationClass::getTableName();
                $relationTableName = $queryLayer->getQueryBuilderRaw()->addTablePrefix($relationTableName);
                $relationColumn = $field->getTo();

                $this->createForeignKey(
                    $tableName,
                    $field->getAttributeName(),
                    $relationTableName,
                    $relationColumn,
                    $field->onDelete,
                    $field->onUpdate,
                    $queryLayer
                );
            }
        }
        $this->createM2MForeignKeys($model);
    }

    /**
     * @param $model Model
     */
    public function createM2MForeignKeys($model)
    {
        $queryLayer = $model->objects()->getQuerySet()->getQueryLayer();

        foreach ($model->getFieldsManager()->getFields() as $field) {
            if ($field instanceof ManyToManyField && !$field->getThrough()) {

                $relationModel = $field->getRelationModel();

                $tableName = $field->getThroughTableName();
                $tableNamePrefix = $queryLayer->getQueryBuilderRaw()->addTablePrefix($tableName);

                $columnTo = $field->getTo();
                $columnThroughTo = $field->getThroughTo();
                $toTableName = $relationModel->getTableName();
                $toTableName = $queryLayer->getQueryBuilderRaw()->addTablePrefix($toTableName);

                $this->createForeignKey(
                    $tableName,
                    $columnThroughTo,
                    $toTableName,
                    $columnTo,
                    $field->onUpdateTo,
                    $field->onDeleteTo,
                    $queryLayer
                );

                $columnFrom = $field->getFrom();
                $columnThroughFrom = $field->getThroughFrom();
                $fromTableName = $model->getTableName();
                $fromTableName = $queryLayer->getQueryBuilderRaw()->addTablePrefix($fromTableName);

                $this->createForeignKey(
                    $tableName,
                    $columnThroughFrom,
                    $fromTableName,
                    $columnFrom,
                    $field->onUpdateFrom,
                    $field->onDeleteFrom,
                    $queryLayer
                );

            }
        }
    }

    /**
     * @param $model Model
     * @param int $mode
     */
    public function dropModelTable($model, $mode = self::DROP_CASCADE)
    {
        $this->dropM2MTables($model, $mode);
        $this->dropModelForeignKeys($model);

        $exists = "";
        if ($this->checkExists) {
            $exists = "IF EXISTS";
        }

        $tableName = $model->getTableName();
        $queryLayer = $model->objects()->getQuerySet()->getQueryLayer();
        $tableName = $queryLayer->getQueryBuilderRaw()->addTablePrefix($tableName);
        $tableNameSafe = $queryLayer->sanitize($tableName);

        $modeQuery = $this->getQueryDropMode($mode);
        $query = "DROP TABLE {$exists} {$tableNameSafe} $modeQuery";
        $queryLayer->getQueryBuilderRaw()->statement($query);
    }

    /**
     * @param $model Model
     * @param int $mode
     */
    public function dropM2MTables($model, $mode = self::DROP_CASCADE)
    {
        $queryLayer = $model->objects()->getQuerySet()->getQueryLayer();
        foreach ($model->getFieldsManager()->getFields() as $field) {
            if ($field instanceof ManyToManyField && !$field->getThrough()) {
                $tableName = $field->getThroughTableName();
                $tableNameSafe = $queryLayer->getQueryBuilderRaw()->addTablePrefix($tableName);
                $tableNameSafe = $queryLayer->sanitize($tableNameSafe);

                $modeQuery = $this->getQueryDropMode($mode);

                $query = "DROP TABLE IF EXISTS {$tableNameSafe} {$modeQuery}";
                $queryLayer->getQueryBuilderRaw()->statement($query);
            }
        }
    }

    /**
     * @param $model Model
     */
    public function dropModelForeignKeys($model)
    {
        $tableName = $model->getTableName();
        $queryLayer = $model->objects()->getQuerySet()->getQueryLayer();
        $tableName = $queryLayer->getQueryBuilderRaw()->addTablePrefix($tableName);

        $fieldsManager = $model->getFieldsManager();
        $fields = $fieldsManager->getFields();

        foreach ($fields as $field) {
            if ($field instanceof HasManyField) {
                $relationClass = $field->modelClass;
                $relationTableName = $relationClass::getTableName();
                $relationTableName = $queryLayer->getQueryBuilderRaw()->addTablePrefix($relationTableName);

                $this->dropForeignKey(
                    $relationTableName,
                    $field->getTo(),
                    $queryLayer
                );
            }
        }
    }
    /**
     * @param $tableName
     * @param $queryLayer QueryLayer
     * @return bool
     */
    public function hasTable($tableName, $queryLayer)
    {
        $query = "SHOW TABLES LIKE '$tableName'";
        /** @var $result \PDOStatement */
        list($result) = $queryLayer->getQueryBuilderRaw()->statement($query);
        return $result->rowCount() > 0;
    }

    /**
     * @param $tableName
     * @param $column
     * @param $queryLayer QueryLayer
     * @return bool
     */
    public function hasForeignKey($tableName, $column, $queryLayer)
    {
        $tableName = $queryLayer->sanitize($tableName);
        $query = "SHOW INDEXES IN $tableName WHERE Column_name = '$column'";
        /** @var $result \PDOStatement */
        list($result) = $queryLayer->getQueryBuilderRaw()->statement($query);
        return $result->rowCount() > 0;
    }

    public function getForeignKeyName($tableName, $column)
    {
        return implode('__', [$tableName, $column]);
    }
    /**
     * @param $tableName
     * @param $column
     * @param $relationTableName
     * @param $relationColumn
     * @param $onDelete
     * @param $onUpdate
     * @param $queryLayer QueryLayer
     * @return bool
     */
    public function createForeignKey($tableName, $column, $relationTableName, $relationColumn, $onDelete, $onUpdate, $queryLayer)
    {
        if (
            !$this->hasForeignKey($tableName, $column, $queryLayer)
            && $this->hasTable($tableName, $queryLayer)
            && $this->hasTable($relationTableName, $queryLayer)) {

            $keyName = $this->getForeignKeyName($tableName, $column);
            $tableName = $queryLayer->sanitize($tableName);
            $relationTableName = $queryLayer->sanitize($relationTableName);
            $column = $queryLayer->sanitize($column);
            $relationColumn = $queryLayer->sanitize($relationColumn);

            $updateReference = $this->getQueryReference($onUpdate, 'CASCADE');
            $deleteReference = $this->getQueryReference($onDelete, 'CASCADE');

            $query = "
                ALTER TABLE {$tableName}
                ADD CONSTRAINT {$keyName} FOREIGN KEY ({$column})
                REFERENCES {$relationTableName}({$relationColumn})
                ON UPDATE {$updateReference}
                ON DELETE {$deleteReference}
            ";
            $queryLayer->getQueryBuilderRaw()->statement($query);
        }
    }

    /**
     * @param $tableName
     * @param $column
     * @param $queryLayer QueryLayer
     * @return bool
     */
    public function dropForeignKey($tableName, $column, $queryLayer)
    {
        if ($this->hasTable($tableName, $queryLayer) && $this->hasForeignKey($tableName, $column, $queryLayer)) {
            $keyName = $this->getForeignKeyName($tableName, $column);
            $tableName = $queryLayer->sanitize($tableName);
            $keyName = $queryLayer->sanitize($keyName);
            $query = "ALTER TABLE {$tableName} DROP FOREIGN KEY {$keyName}";
            $queryLayer->getQueryBuilderRaw()->statement($query);
        }

    }

    public function getQueryReference($reference, $default = 'CASCADE')
    {
        switch ($reference) {
            case ForeignField::CASCADE:
                return 'CASCADE';
            case ForeignField::NO_ACTION:
                return 'NO ACTION';
            case ForeignField::RESTRICT:
                return 'RESTRICT';
            case ForeignField::SET_DEFAULT:
                return 'SET DEFAULT';
            case ForeignField::SET_NULL:
                return 'SET NULL';
        }
        return $default;
    }

    public function getQueryDropMode($mode = self::DROP_CASCADE, $default = 'CASCADE')
    {
        switch ($mode) {
            case self::DROP_CASCADE:
                return 'CASCADE';
            case self::DROP_RESTRICT:
                return 'NO RESTRICT';
        }
        return $default;
    }
}