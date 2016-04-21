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
 * @date 15/04/16 11:02
 */

namespace Phact\Orm;


use Phact\Main\Phact;

class Query
{
    protected $_connectionName = 'default';
    protected $_adapter;

    public function setConnectionName($connectionName)
    {
        $this->_connectionName = $connectionName;
    }

    public function getConnectionName()
    {
        return $this->_connectionName;
    }

    public function getConnection()
    {
        $connectionName = $this->getConnectionName();
        return Phact::app()->db->getConnection($connectionName);
    }

    public function getQueryConnection()
    {
        return $this->getConnection()->getQueryConnection();
    }

    /**
     * @return \Pixie\QueryBuilder\Adapters\BaseAdapter
     */
    public function getAdapter()
    {
        if (!$this->_adapter) {
            $queryConnection = $this->getQueryConnection();
            $adapter = $queryConnection->getAdapter();
            $adapterClass = '\\Pixie\\QueryBuilder\\Adapters\\' . ucfirst($adapter);
            $this->_adapter = new $adapterClass($queryConnection);
        }
        return $this->_adapter;
    }

    public function getQueryBuilder()
    {

        return $this->getConnection()->getQueryBuilder();
    }

    public function insert($tableName, $data)
    {
        $qb = $this->getQueryBuilder();
        return $qb->table($tableName)->insert($data);
    }

    public function updateByPk($tableName, $pkName, $pkValue, $data)
    {
        $qb = $this->getQueryBuilder();
        $statement = $qb->table($tableName)->where($pkName, $pkValue)->update($data);
        $code = $statement->errorCode();
        return $code === "00000";
    }
}