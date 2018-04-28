<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 05/05/16 06:59
 */

namespace Phact\Orm;

use Phact\Helpers\SmartProperties;

class Expression
{
    use SmartProperties;

    /**
     * @var string
     */
    protected $_expression;

    /**
     * @var array
     */
    protected $_params = [];

    /**
     * Handle aliases like {id} or {user__places__id}
     * @var bool
     */
    protected $_useAliases = true;

    public function __construct($expression, $params = [], $useAliases = true)
    {
        $this->_expression = $expression;
        $this->_params = $params;
        $this->_useAliases;
    }

    public function getExpression()
    {
        return $this->_expression;
    }

    public function getParams()
    {
        return $this->_params;
    }

    public function getUseAliases()
    {
        return $this->_expression;
    }

    public function getAliases()
    {
        if (preg_match_all('#\{(.+?)\}#', $this->_expression, $match)) {
            return $match[1];
        }
        return [];
    }
}