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
 * @date 12/04/16 18:08
 */

namespace Phact\Orm;

use Phact\Orm\Adapters\Adapter;

class Connection
{
    public $driver = 'mysql';
    public $config = [];

    /**
     * @var \PDO
     */
    protected $_pdo;

    protected $_queryConnection;

    /**
     * @var Adapter
     */
    protected $_adapter;
    
    public function getAdapter()
    {
        if (!$this->_adapter) {
            $adapter = '\\Phact\\Orm\\Adapters\\' . ucfirst(strtolower($this->driver));
            /** @var Adapter _adapter */
            $this->_adapter = new $adapter();
            $pdo = $this->_adapter->connect($this->config);
            $this->_pdo = $pdo;
        }
        return $this->_adapter;
    }

    public function getQueryBuilder()
    {
        return new QueryBuilder($this);
    }

    public function getPdo()
    {
        return $this->_pdo;
    }
}