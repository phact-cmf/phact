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

class TableManager
{
    public $defaultEngine = 'InnoDB';
    public $defaultCharset = 'utf8';
    public $checkExists = true;

    public function create($models = [])
    {
        foreach ($models as $model) {
            $this->createModelTable($model);
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
}