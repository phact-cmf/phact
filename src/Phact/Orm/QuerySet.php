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
 * @date 14/04/16 07:53
 */

namespace Phact\Orm;


use Phact\Helpers\SmartProperties;

/**
 * Class QuerySet
 *
 * @property $queryLayer QueryLayer
 *
 * @package Phact\Orm
 */
class QuerySet
{
    use SmartProperties;

    /**
     * @var Model
     */
    public $model;

    protected $_queryLayer;

    public function getQueryLayer()
    {
        if (is_null($this->_queryLayer)) {
            $this->_queryLayer = new QueryLayer();
        }
        $this->_queryLayer->querySet = $this;
        return $this->_queryLayer;
    }

    public function createModel($row)
    {
        $class = $this->model->className();
        /* @var $model Model */
        $model = new $class;
        $model->setDbData($row);
        return $model;
    }

    public function createModels($data)
    {
        $result = [];
        foreach ($data as $row) {
            $result[] = $this->createModel($row);
        }
        return $result;
    }

    public function all()
    {
        $data = $this->getQueryLayer()->all();
        return $this->createModels($data);
    }

    public function get()
    {
        $row = $this->getQueryLayer()->get();
        return $row ? $this->createModel($row) : null;
    }
}