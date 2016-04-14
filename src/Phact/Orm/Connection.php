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

use Pixie\QueryBuilder\QueryBuilderHandler;

class Connection
{
    public $driver = 'mysql';
    public $config = [];

    protected $_queryConnection;

    /**
     * @return \Pixie\Connection
     */
    public function getQueryConnection()
    {
        if (is_null($this->_queryConnection)) {
            $this->_queryConnection = new \Pixie\Connection($this->driver, $this->config);
        }
        return $this->_queryConnection;
    }

    public function getQueryBuilder()
    {
        $connection = $this->getQueryConnection();
        return new QueryBuilderHandler($connection);
    }
}