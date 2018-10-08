<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 10/09/2018 15:08
 */

namespace Phact\Orm;


use Doctrine\DBAL\Query\Expression\CompositeExpression;

class WrappedCompositeExpression extends CompositeExpression
{
    /**
     * @var null|string
     */
    protected $_wrapper = 'NOT';

    /**
     * @return null|string
     */
    public function getWrapper()
    {
        return $this->_wrapper;
    }

    /**
     * @param null|string $wrapper
     */
    public function setWrapper($wrapper)
    {
        $this->_wrapper = $wrapper;
    }

    public function __toString()
    {
        $expression = parent::__toString();
        if ($this->_wrapper) {
            $expression = "{$this->_wrapper} ({$expression})";
        }
        return $expression;
    }
}