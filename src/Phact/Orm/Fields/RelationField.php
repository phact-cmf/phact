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
 * @date 13/04/16 08:11
 */

namespace Phact\Orm\Fields;

/**
 * Class RelationField
 *
 * @package Phact\Orm\Fields
 */
abstract class RelationField extends Field
{
    public $modelClass;

    abstract public function getRelationJoins();

    /**
     * @return \Phact\Orm\Model
     */
    public function getRelationModel()
    {
        $class = $this->modelClass;
        return new $class();
    }

    public function getIsMany()
    {
        return false;
    }

    public function getSqlType()
    {
        return '';
    }
}