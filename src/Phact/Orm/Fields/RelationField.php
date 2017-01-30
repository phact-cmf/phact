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
use Phact\Exceptions\InvalidConfigException;

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

    public function getRelationModelClass()
    {
        $modelClass = $this->modelClass;
        if (!$modelClass || !class_exists($modelClass)) {
            $class = static::class;
            throw new InvalidConfigException("Model class in {$class} must be defined and valid class");
        }
        return $modelClass;
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