<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 19/04/16 08:29
 */

namespace Phact\Orm\Fields;
use Phact\Form\Fields\DropDownField;
use Phact\Helpers\Configurator;
use Phact\Orm\ManyToManyManager;
use Phact\Orm\QuerySet;

/**
 * Class HasManyField
 *
 * @property $to string Related model field
 * @property $from string Current model field
 *
 * @package Phact\Orm\Fields
 */
class ManyToManyField extends RelationField
{
    /**
     * Related model field
     *
     * @var string
     */
    protected $_to = 'id';

    /**
     * Current model field
     *
     * @var string
     */
    protected $_from = 'id';

    protected $_through;
    protected $_throughFrom;
    protected $_throughTo;
    protected $_throughName;

    protected $_throughTableName;

    protected $_throughModel;

    /**
     * Back name
     * For example:
     *
     * Model Author
     *
     * 'books' => [
     *  'class' => ManyToManyField::class,
     *  'modelClass' => Book::class,
     *  'back' => 'authors'
     * ]
     *
     * Model Book
     *
     * 'authors' => [
     *  'class' => ManyToManyField::class,
     *  'modelClass' => Author::class,
     *  'back' => 'books'
     * ]
     *
     * @var null
     */
    public $back = null;

    public $onUpdateTo = ForeignField::CASCADE;
    public $onDeleteTo = ForeignField::CASCADE;

    public $onUpdateFrom = ForeignField::CASCADE;
    public $onDeleteFrom = ForeignField::CASCADE;

    public $virtual = true;
    public $editable = true;
    public $null = true;
    public $blank = true;

    public $managerClass = ManyToManyManager::class;

    /**
     * Attribute of related model that contains name
     * @var string|null
     */
    public $nameAttribute = null;

    public function getFrom()
    {
        return $this->_from;
    }

    public function setFrom($from)
    {
        $this->_from = $from;
    }

    public function getTo()
    {
        return $this->_to;
    }

    public function setTo($to)
    {
        $this->_to = $to;
    }

    public function getThrough()
    {
        return $this->_through;
    }

    public function getIsLoop()
    {
        return $this->getOwnerModelClass() == $this->getRelationModelClass();
    }

    public function setThrough($through)
    {
        $this->_through = $through;
    }

    public function getThroughName()
    {
        if (!$this->_throughName && ($through = $this->getThrough())) {
            $throughName = $through::classNameUnderscore();
            $this->_throughName = $throughName;
        }
        return $this->_throughName;
    }

    public function setThroughName($throughName)
    {
        $this->_throughName = $throughName;
    }

    public function getThroughTo()
    {
        if (!$this->_throughTo) {
            if ($this->getIsLoop()) {
                if ($this->back) {
                    $this->_throughTo = "from_{$this->getFrom()}";
                } else {
                    $this->_throughTo = "to_{$this->getTo()}";
                }
            } else {
                $class = $this->getRelationModelClass();
                $modelName = $class::classNameUnderscore();
                $this->_throughTo = "{$modelName}_{$this->getTo()}";
            }
        }
        return $this->_throughTo;
    }

    public function setThroughTo($throughTo)
    {
        $this->_throughTo = $throughTo;
    }

    public function getThroughFrom()
    {
        if (!$this->_throughFrom) {
            if ($this->getIsLoop()) {
                if ($this->back) {
                    $this->_throughFrom = "to_{$this->getTo()}";
                } else {
                    $this->_throughFrom = "from_{$this->getFrom()}";
                }
            } else {
                $model = $this->getOwnerModelClass();
                $modelName = $model::classNameUnderscore();
                $this->_throughFrom = "{$modelName}_{$this->getFrom()}";
            }
        }
        return $this->_throughFrom;
    }

    public function setThroughFrom($throughFrom)
    {
        $this->_throughFrom = $throughFrom;
    }

    public function getThroughTableName()
    {
        if (!$this->_throughTableName) {
            if ($through = $this->getThrough()) {
                $this->_throughTableName = $through::getTableName();
            } elseif ($this->getIsLoop()) {
                $model = $this->getOwnerModelClass();
                if ($this->back) {
                    $names = [$model::getTableName(), $this->back];
                } else {
                    $names = [$model::getTableName(), $this->getName()];
                }
                $this->_throughTableName = implode('_', $names);
            } else {
                $model = $this->getOwnerModelClass();
                $modelClass = $this->getRelationModelClass();
                $names = [$model::getTableName(), $modelClass::getTableName()];
                sort($names);
                $this->_throughTableName = implode('_', $names);
            }
        }
        return $this->_throughTableName;
    }

    public function setThroughTableName($tableName)
    {
        $this->_throughTableName = $tableName;
    }

    public function getAdditionalFields()
    {
        if ($through = $this->getThrough()) {
            $throughName = $this->getThroughName();
            return [
                "$throughName" => [
                    'class' => HasManyField::class,
                    'modelClass' => $through,
                    'throughFor' => $this->getName(),
                    'from' => $this->getFrom(),
                    'to' => $this->getThroughFrom()
                ]
            ];
        } else {
            return [];
        }
    }

    public function getRelationJoins()
    {
        $relationModelClass = $this->getRelationModelClass();
        if ($throughName = $this->getThroughName()) {
            return [
                $throughName,
                [
                    'table' => $relationModelClass::getTableName(),
                    'from' => $this->getThroughTo(),
                    'to' => $this->getTo()
                ]
            ];
        } else {
            return [
                [
                    'table' => $this->getThroughTableName(),
                    'from' => $this->getFrom(),
                    'to' => $this->getThroughFrom()
                ],
                [
                    'table' => $relationModelClass::getTableName(),
                    'from' => $this->getThroughTo(),
                    'to' => $this->getTo()
                ]
            ];
        }
    }

    public function getIsMany()
    {
        return true;
    }

    public function getValue($aliasConfig = NULL)
    {
        return $this->getManager();
    }

    /**
     * @return ManyToManyManager
     */
    public function getManager()
    {
        $relationModel = $this->getRelationModel();
        $manager = new $this->managerClass($relationModel);

        $backField = null;
        $backThroughName = null;
        $backThroughField = null;

        /** @var $backField self */
        if ($this->back && ($backField = $relationModel->getField($this->back)) && ($backThroughName = $backField->getThroughName())) {
            $backThroughField = $backField->getThroughTo();
        }

        return Configurator::configure($manager, [
            'backField' => $backField,
            'backThroughName' => $backThroughName,
            'backThroughField' => $backThroughField,

            'through' => $this->getThrough(),
            'throughTable' => $this->getThroughTableName(),
            'throughFromField' => $this->getThroughFrom(),
            'throughToField' => $this->getThroughTo(),

            'toField' => $this->getTo(),
            'fromField' => $this->getFrom(),

            'fieldName' => $this->getName(),

            'ownerModel' => $this->getModel()
        ]);
    }

    public function afterSave()
    {
        $attribute = $this->getAttribute();
        if (!is_null($attribute)) {
            $manager = $this->getManager();
            $manager->set($attribute);
            $this->setAttribute(null);
            $this->setOldAttribute(null);
        }
        parent::afterSave();
    }

    public function getBlankValue()
    {
        return null;
    }

    public function getAttributeName()
    {
        return $this->getName();
    }

    public function setUpFormField($config = [])
    {
        if (!isset($config['class'])) {
            $config['class'] = DropDownField::class;
            $config['multiple'] = true;
        }
        $choices = [];
        $class = $this->getRelationModelClass();
        /** @var QuerySet $qs */
        $qs = $class::objects()->getQuerySet();
        if ($this->nameAttribute) {
            $choices = $choices + $qs->choices('pk', $this->nameAttribute);
        } else {
            $objects = $qs->all();
            foreach ($objects as $object) {
                $choices[$object->pk] = (string) $object;
            }
        }
        $config['choices'] = $choices;
        return parent::setUpFormField($config);
    }
}