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
 * @date 07/04/17 15:23
 */

namespace Phact\Orm\Having;


use Phact\Orm\Aggregations\Aggregation;

class Having
{
    /**
     * @var Aggregation
     */
    protected $_aggregation;

    /**
     * @var string
     */
    protected $_condition;

    public function __construct(Aggregation $aggregation, $condition)
    {
        $this->_aggregation = $aggregation;
        $this->_condition = $condition;
    }

    public function getAggregation()
    {
        return $this->_aggregation;
    }

    public function getCondition()
    {
        return $this->_condition;
    }
}