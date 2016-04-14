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

use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;

/**
 * Class QueryLayer
 *
 * @property $model Model
 *
 * @package Phact\Orm
 */
class QueryLayer
{
    use SmartProperties;

    /**
     * @var \Phact\Orm\QuerySet
     */
    public $querySet;

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
        $metaData = $this->getMetaData();
        return isset($metaData['tableName']) ? $metaData['tableName'] : $this->getModel()->getTableName();
    }

    public function getConnectionName()
    {
        $metaData = $this->getMetaData();
        return isset($metaData['connection']) ? $metaData['connection'] : 'default';
    }

    public function getQueryBuilder()
    {
        $connectionName = $this->getConnectionName();
        $connection = Phact::app()->db->getConnection($connectionName);
        return $connection->getQueryBuilder();
    }

    public function all()
    {
        $qb = $this->getQueryBuilder();
        $query = $qb->table([$this->getTableName()])->setFetchMode(\PDO::FETCH_ASSOC);
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