<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 15/04/16 11:02
 */

namespace Phact\Orm;


use Phact\Main\Phact;
use Phact\Orm\Adapters\Adapter;

class Query
{
    protected $_connectionName = 'default';
    protected $_adapter;

    public function __construct($connectionName = null)
    {
        if($connectionName){
            $this->_connectionName = $connectionName;
        }
    }
    
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

    public function getQueryBuilder()
    {
        return $this->getConnection()->createQueryBuilder();
    }

    public function insert($tableName, $data)
    {
        $qb = $this->getQueryBuilder();
        $statement = $qb->insert($tableName);
        foreach ($data as $field => $value) {
            $placeholder = $qb->createNamedParameter($value);
            $qb->setValue($field, $placeholder);
        }
        $statement->execute();
        return $statement->getConnection()->lastInsertId();
    }

    public function updateByPk($tableName, $pkName, $pkValue, $data)
    {
        $qb = $this->getQueryBuilder()->update($tableName)->where($pkName, $pkValue);
        foreach ($data as $field => $value) {
            $placeholder = $qb->createNamedParameter($value);
            $qb->set($field, $placeholder);
        }
        return $qb->execute();
    }

    public function delete($tableName, $pkName, $pkValue)
    {
        $qb = $this->getQueryBuilder();
        return $qb->delete($tableName)->where($qb->expr()->eq($pkName, $pkValue))->execute();
    }
}