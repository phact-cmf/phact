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
 * @date 14/04/16 07:54
 */

namespace Phact\Orm;

use Phact\Helpers\Configurator;
use Phact\Helpers\SmartProperties;

/**
 * Class Manager
 *
 * @property $querySet \Phact\Orm\QuerySet
 * @property $model \Phact\Orm\Model
 *
 * @package Phact\Orm
 */
class Manager
{
    use SmartProperties;

    protected $_model;

    public $querySetClass = QuerySet::class;

    public function __construct(Model $model)
    {
        $this->_model = $model;
    }

    public function getModel()
    {
        return $this->_model;
    }

    /**
     * @return \Phact\Orm\QuerySet
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    public function getQuerySet()
    {
        return Configurator::create($this->querySetClass, [
            'model' => $this->model
        ]);
    }

    public function all()
    {
        return $this->getQuerySet()->all();
    }

    public function get()
    {
        return $this->getQuerySet()->get();
    }
}