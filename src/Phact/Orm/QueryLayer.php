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
 * @date 14/04/16 08:24
 */

namespace Phact\Orm;

use Exception;
use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;

/**
 * Class QueryLayer
 *
 * @property $model \Phact\Orm\Model
 * @property $querySet \Phact\Orm\QuerySet
 *
 * @package Phact\Orm
 */
class QueryLayer
{
    use SmartProperties;

    protected $_query;

    /**
     * @var \Phact\Orm\QuerySet
     */
    protected $_querySet;

    protected $_aliases;

    public function __construct($querySet)
    {
        $this->_querySet = $querySet;
        $this->setAliases();
    }

    /**
     * @return \Phact\Orm\QuerySet
     */
    public function getQuerySet()
    {
        return $this->_querySet;
    }

    /**
     * @return \Phact\Orm\Query
     */
    public function getQuery()
    {
        return $this->getModel()->getQuery();
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->querySet->model;
    }

    public function getMetaData()
    {
        return $this->getModel()->getMetaData();
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->getModel()->getTableName();
    }

    public function getQueryBuilder()
    {
        return $this->getQuery()->getQueryBuilder();
    }

    public function getQueryAdapter()
    {
        return $this->getQuery()->getAdapter();
    }

    public function sanitize($value)
    {
        return $this->getQueryAdapter()->wrapSanitizer($value);
    }

    public function setAliases()
    {
        $this->_aliases = [];
        $tables = [$this->getTableName() => '__this'];
        $relations = $this->querySet->getRelations();
        foreach ($relations as $relationName => $relation) {
            if (isset($relation['joins']) && is_array($relation['joins'])) {
                foreach ($relation['joins'] as $join) {
                    if (is_array($join) && isset($join['table'])) {
                        $tableName = $join['table'];
                        if (isset($tables[$tableName])) {
                            $this->setAlias($relationName, $tableName);
                        } else {
                            $tables[$join['table']] = $relationName;
                        }
                    }
                }
            }
        }
    }

    public function getAliases()
    {
        return $this->_aliases;
    }

    public function setAlias($relationName, $tableName)
    {
        $this->_aliases[$relationName . '#' . $tableName] = $tableName . '_' . (count($this->_aliases) + 1);
    }

    public function getAlias($relationName, $tableName)
    {
        if (isset($this->_aliases[$relationName . '#' . $tableName])) {
            return $this->_aliases[$relationName . '#' . $tableName];
        }
        return null;
    }

    public function getTableOrAlias($relationName, $tableName)
    {
        if ($alias = $this->getAlias($relationName, $tableName)) {
            return $alias;
        }
        return $tableName;
    }

    public function getRelationModel($relationName)
    {
        if ($relationName) {
            $relation = $this->getQuerySet()->getRelation($relationName);
            /** @var $model Model */
            return isset($relation['model']) ? $relation['model'] : null;
        } else {
            return $this->getModel();
        }
    }

    public function getRelationTable($relationName)
    {
        $model = $this->getRelationModel($relationName);
        return $model->getTableName();
    }

    public function columnAlias($relationName, $attribute, $tableName = null)
    {
        $tableName = $this->getTableOrAlias($relationName, $tableName ?: $this->getRelationTable($relationName));
        return $this->column($tableName, $attribute);
    }

    public function column($tableName, $attribute)
    {
        return $tableName . '.' . $attribute;
    }

    /**
     * @param $query \Pixie\QueryBuilder\QueryBuilderHandler
     * @return \Pixie\QueryBuilder\QueryBuilderHandler
     * @throws Exception
     */
    public function processJoins($query)
    {
        $relations = $this->getQuerySet()->getRelations();
        foreach ($relations as $relationName => $relation) {
            // A relation and a table on which a join is to be build
            $currentRelationName = $this->getQuerySet()->parentRelationName($relationName);
            $currentTable = $this->getRelationTable($currentRelationName);

            if (isset($relation['joins']) && is_array($relation['joins'])) {
                foreach ($relation['joins'] as $join) {
                    if (is_array($join)) {
                        if (isset($join['table']) && isset($join['from']) && isset($join['to'])) {
                            $attributeFrom = $join['from'];
                            $attributeTo = $join['to'];

                            $tableName = $join['table'];
                            $connectTable = $tableName;

                            if ($alias = $this->getAlias($relationName, $tableName)) {
                                $aliasedTable = $query->addTablePrefix($tableName);
                                $connectTable = $this->sanitize($aliasedTable) . ' AS ' . $this->sanitize($alias);
                                $connectTable = $query->raw($connectTable);
                            }

                            $query->join(
                                $connectTable,
                                $this->columnAlias($currentRelationName, $attributeFrom, $currentTable),
                                '=',
                                $this->column($alias ?: $tableName, $attributeTo),
                                isset($join['type']) ? $join['type'] : 'inner'
                            );

                            // We change the current join
                            $currentTable = $tableName;
                            $currentRelationName = $relationName;
                        } else {
                            throw new Exception('Invalid join configuration. Please, check your relation fields.');
                        }
                    } elseif (is_string($join) && ($joinRelation = $this->getQuerySet()->getRelation($join))) {
                        /* @var $model Model */
                        $model = $joinRelation['model'];
                        $currentRelationName = $join;
                        $currentTable = $model->getTableName();
                    }
                }
            }
        }
        return $query;
    }

    public function all()
    {
        $qb = $this->getQueryBuilder();
        $query = $qb->table([$this->getTableName()])->setFetchMode(\PDO::FETCH_ASSOC);
        $qs = $this->getQuerySet();

        $select = $this->column($this->getTableName(), '*');
        if ($qs->getHasManyRelations()) {
            $query->selectDistinct($select);
        } else {
            $query->select($select);
        }

        $query = $this->processJoins($query);
        var_dump($query->getQuery()->getSql());
        $result = $query->get();
        return $result;
    }

    public function get()
    {
        $qb = $this->getQueryBuilder();
        $query = $qb->table([$this->getTableName()])->setFetchMode(\PDO::FETCH_ASSOC);
        $result = $query->first();
        return $result;
    }
}