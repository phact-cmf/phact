<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 13/05/16 07:28
 */

namespace Phact\Orm;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index as DBALIndex;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Phact\Orm\Fields\ForeignField;
use Phact\Orm\Index as PhactIndex;
use Phact\Orm\Configuration\ConfigurationProvider;
use Phact\Orm\Fields\Field;
use Phact\Orm\Fields\ManyToManyField;

class TableManager
{
    public $checkExists = true;
    public $addFields = true;
    public $processFk = true;

    /**
     * @param array $models
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Phact\Exceptions\UnknownPropertyException
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    public function create($models = [])
    {
        foreach ($models as $model) {
            $this->createModelTable($model);
        }

        if ($this->processFk) {
            foreach ($models as $model) {
                $this->createModelTable($model, true);
            }
        }

        foreach ($models as $model) {
            $this->createM2MTables($model);
        }

        return true;
    }

    public function getFKConstrains(Model $model)
    {
        $fk = [];
        foreach ($model->getFieldsManager()->getFields() as $field) {
            if ($field instanceof ForeignField) {
                $toModel = $field->getRelationModel();
                $options = $this->getConstrainOptions($field->onUpdate, $field->onDelete);
                $fk[] = new ForeignKeyConstraint([$field->getFrom()], $toModel->getTableName(), [$field->getTo()], null, $options);
            }
        }

        return $fk;
    }

    /**
     * @param array $models
     * @param null $mode @deprecated
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Phact\Exceptions\UnknownPropertyException
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    public function drop($models = [], $mode = null)
    {
        foreach ($models as $model) {
            $this->dropModelForeignKeys($model);
        }

        foreach ($models as $model) {
            $this->dropModelTable($model, $mode);
        }

        return true;
    }

    /**
     * @param $model Model
     * @return \Doctrine\DBAL\Schema\AbstractSchemaManager
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    public function getSchemaManager($model)
    {
        $connectionName = $model->getConnectionName();
        $configuration = ConfigurationProvider::getInstance()->getManager();
        $connectionManager = $configuration->getConnectionManager();
        $connection = $connectionManager->getConnection($connectionName);
        return $connection->getSchemaManager();
    }

    /**
     * @param $model Model
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Phact\Exceptions\UnknownPropertyException
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    public function createModelTable($model, bool $processFk = false)
    {
        $tableName = $model->getTableName();
        $columns = $this->createColumns($model);

        $dbalIndexes = $this->convertIndexes($model->getIndexes());

        $fk = [];
        if ($processFk) {
            $fk = $this->getFKConstrains($model);
        }

        $table = new Table($tableName, $columns, $dbalIndexes, $fk);
        $schemaManager = $this->getSchemaManager($model);
        if (!$schemaManager->tablesExist([$tableName])) {
            $schemaManager->createTable($table);
        } else {
            $tableExists = $schemaManager->listTableDetails($tableName);
            $comparator = new Comparator();
            if ($diff = $comparator->diffTable($tableExists, $table)) {
                $schemaManager->alterTable($diff);
            }
        }
    }


    /**
     * @param $model Model
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Phact\Exceptions\UnknownPropertyException
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    public function createM2MTables($model)
    {
        $handledTables = [];
        $schemaManager = $this->getSchemaManager($model);
        foreach ($model->getFieldsManager()->getFields() as $field) {
            if ($field instanceof ManyToManyField && !$field->getThrough()) {
                $tableName = $field->getThroughTableName();

                if (in_array($tableName, $handledTables)) {
                    continue;
                }

                $columns = [];

                $toModelClass = $field->modelClass;
                /** @var $toModel Model */
                $toModel = new $toModelClass();

                if (!$schemaManager->tablesExist([$toModel->getTableName()])) {
                    continue;
                }

                $to = $field->getTo();
                $toColumnName = $field->getThroughTo();
                $toField = $toModel->getField($to);
                $toColumnOptions = $toField->getColumnOptions();
                if (isset($toColumnOptions['autoincrement'])) {
                    unset($toColumnOptions['autoincrement']);
                }
                $columns[] = new Column($toColumnName, Type::getType($toField->getType()), $toColumnOptions);

                $from = $field->getFrom();
                $fromColumnName = $field->getThroughFrom();
                $fromField = $model->getField($from);
                $fromColumnOptions = $fromField->getColumnOptions();
                if (isset($fromColumnOptions['autoincrement'])) {
                    unset($fromColumnOptions['autoincrement']);
                }
                $columns[] = new Column($fromColumnName, Type::getType($toField->getType()), $fromColumnOptions);

                $fk = [];
                if ($this->processFk) {
                    $toM2MField = $this->getBackM2M($toModel, $model::class);
                    $M2MOptions = $this->getM2MConstrainOptions($field, $toM2MField);
                    $toOptions = $M2MOptions['to'] ?? [];
                    $fromOptions = $M2MOptions['from'] ?? [];
                    $fk[] = new ForeignKeyConstraint([$toColumnName], $toModel->getTableName(), [$to], null, $toOptions);
                    $fk[] = new ForeignKeyConstraint([$fromColumnName], $model->getTableName(), [$from], null, $fromOptions);
                }

                $table = new Table($tableName, $columns, [], $fk);
                if (!$schemaManager->tablesExist([$tableName])) {
                    $schemaManager->createTable($table);
                } else {
                    $tableExists = $schemaManager->listTableDetails($tableName);
                    $comparator = new Comparator();
                    if ($diff = $comparator->diffTable($tableExists, $table)) {
                        $schemaManager->alterTable($diff);
                    }
                }

                $handledTables[] = $tableName;
            }
        }
    }

    public function createColumns($model)
    {
        $fieldsManager = $model->getFieldsManager();
        $fields = $fieldsManager->getFields();
        $columns = [];
        /** @var Field $field */
        foreach ($fields as $field) {
            $attribute = $field->getAttributeName();
            if ($attribute && !$field->virtual) {
                $columnName = $attribute;
                $column = new Column($columnName, Type::getType($field->getType()), $field->getColumnOptions());
                $columns[$columnName] = $column;
            }
        }
        return $columns;
    }

    /**
     * @param $model Model
     * @param int $mode @deprecated
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Phact\Exceptions\UnknownPropertyException
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    public function dropModelTable($model, $mode = null)
    {
        $this->dropM2MTables($model, $mode);
        $this->dropModelForeignKeys($model);

        $tableName = $model->getTableName();
        $schemaManager = $this->getSchemaManager($model);
        if ($schemaManager->tablesExist([$tableName])) {
            $this->getSchemaManager($model)->dropTable($tableName);
        }
    }

    /**
     * @param $model Model
     * @param $mode @deprecated
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    public function dropM2MTables($model, $mode = null)
    {
        $schemaManager = $this->getSchemaManager($model);
        foreach ($model->getFieldsManager()->getFields() as $field) {
            if ($field instanceof ManyToManyField && !$field->getThrough()) {
                $tableName = $field->getThroughTableName();
                if ($schemaManager->tablesExist([$tableName])) {
                    $schemaManager->dropTable($tableName);
                }
            }
        }
    }

    /**
     * @param $model Model
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    public function dropModelForeignKeys($model)
    {
        $tableName = $model->getTableName();
        $schemaManager = $this->getSchemaManager($model);
        foreach ($schemaManager->listTableForeignKeys($tableName) as $constraint) {
            $schemaManager->dropForeignKey($constraint, $tableName);
        }
    }

    /**
     * @param PhactIndex[] $indexes
     * @return DBALIndex[]
     * @throws \Exception
     */
    public function convertIndexes(array $indexes)
    {
        $dbalIndexes = [];
        foreach ($indexes as $index) {
            if (!($index instanceof PhactIndex)) {
                throw new \Exception('Invalid index object. Expected ' . PhactIndex::class);
            }
            $dbalIndexes[] = new DBALIndex(
                $index->getIndexName(),
                $index->getColumns(),
                $index->isUnique(),
                $index->isPrimary(),
                $index->getFlags(),
                $index->getOptions()
            );
        }
        return $dbalIndexes;
    }

    public function getBackM2M(Model $toModel, string $targetClass): ?ManyToManyField
    {
        foreach ($toModel->getFieldsManager()->getFields() as $field) {
            if ($field instanceof ManyToManyField && $field->modelClass === $targetClass) {
                return $field;
            }
        }

        return null;
    }

    public function getM2MConstrainOptions(ManyToManyField $field, ?ManyToManyField $backField = null): array
    {
        if ($backField) {
            $onUpdateTo = max($field->onUpdateTo, $backField->onUpdateFrom);
            $onDeleteTo = max($field->onDeleteTo, $backField->onDeleteFrom);
            $onUpdateFrom = max($field->onUpdateFrom, $backField->onUpdateTo);
            $onDeleteFrom = max($field->onDeleteFrom, $backField->onDeleteTo);
        } else {
            $onUpdateTo = $field->onUpdateTo;
            $onDeleteTo = $field->onDeleteTo;
            $onUpdateFrom = $field->onUpdateTo;
            $onDeleteFrom = $field->onDeleteTo;
        }

        return [
            'to' => $this->getConstrainOptions($onUpdateTo, $onDeleteTo),
            'from' => $this->getConstrainOptions($onUpdateFrom, $onDeleteFrom)
        ];
    }

    public function getConstrainOptions(int $onUpdate, int $onDelete): array
    {
        $options = [];
        $onUpdate = $this->convertConstrain($onUpdate);
        $onDelete = $this->convertConstrain($onDelete);

        if ($onUpdate) {
            $options['onUpdate'] = $onUpdate;
        }

        if ($onDelete) {
            $options['onDelete'] = $onDelete;
        }

        return $options;
    }

    public function convertConstrain(int $const): ?string
    {
        return match ($const) {
            ForeignField::CASCADE => 'CASCADE',
            ForeignField::SET_NULL => 'SET NULL',
            ForeignField::NO_ACTION => 'NO ACTION',
            ForeignField::RESTRICT => 'RESTRICT',
            ForeignField::SET_DEFAULT => 'SET DEFAULT',
            default => null
        };
    }
}