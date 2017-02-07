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
 * @date 16/01/17 14:20
 */

namespace Phact\Orm;


use Exception;
use Phact\Orm\Fields\ForeignField;
use Phact\Orm\Fields\IntField;
use Phact\Orm\Fields\TreeForeignField;

/**
 * Class TreeModel
 * @package Phact\Orm
 *
 * @method static TreeManager objects($model = null)
 *
 * @property int lft
 * @property int rgt
 * @property int root
 * @property int depth
 * @property TreeModel|null parent
 */
abstract class TreeModel extends Model
{
    public static $nameAttribute = null;

    public static function getFields()
    {
        return [
            'parent' => [
                'class' => TreeForeignField::class,
                'modelClass' => static::class,
                'null' => true,
                'nameAttribute' => static::$nameAttribute,
                'label' => 'Parent'
            ],
            'lft' => [
                'class' => IntField::class,
                'editable' => false
            ],
            'rgt' => [
                'class' => IntField::class,
                'editable' => false
            ],
            'root' => [
                'class' => IntField::class,
                'editable' => false
            ],
            'depth' => [
                'class' => IntField::class,
                'editable' => false
            ]
        ];
    }

    public static function objectsManager($model = null)
    {
        if (!$model) {
            $class = static::class;
            $model = new $class();
        }
        return new TreeManager($model);
    }

    public function beforeSave()
    {
        parent::beforeSave();
        if ($this->getIsNew()) {
            if ($this->parent) {
                $this->setAsLastOf($this->parent);
            } else {
                $this->makeRoot();
            }
        }
    }

    public function afterSave()
    {
        if (!$this->getIsNew()) {
            if ($this->getIsChangedAttribute('parent_id')) {
                if ($this->parent) {
                    $this->setAsLastOf($this->parent);
                } else {
                    $this->makeRoot();
                }
                $this->fetchTreePosition();
            }
        }
        parent::afterSave();
    }

    public function fetchTreePosition()
    {
        $data = $this->objects()->filter([
            'pk' => $this->pk
        ])->values([
            'lft',
            'rgt',
            'root',
            'parent_id',
            'depth'
        ]);
        if (isset($data[0])) {
            $this->setAttributes(
                $data[0]
            );
        }
    }
    
    public function setAsFirstOf($target)
    {
        if ($this->getIsNew()) {
            return $this->addNode($target, $target->lft + 1, 1);
        } else {
            return $this->moveNode($target, $target->rgt, 1);
        }
    }

    public function setAsLastOf($target)
    {
        if ($this->getIsNew()) {
            return $this->addNode($target, $target->rgt, 1);
        } else {
            return $this->moveNode($target, $target->lft + 1, 1);
        }
    }

    /**
     * Set (move/add) current not before target node
     *
     *
     * @param $target
     * @return bool|TreeModel
     * @throws Exception
     */
    public function setBefore($target)
    {
        if ($this->getIsNew()) {
            return $this->addNode($target, $target->lft, 0);
        } else {
            return $this->moveNode($target, $target->lft, 0);
        }

    }

    /**
     * Set (move/add) current not after target node
     *
     * @param $target
     * @return bool|TreeModel
     * @throws Exception
     */
    public function setAfter($target)
    {
        if ($this->getIsNew()) {
            return $this->addNode($target, $target->rgt + 1, 0);
        } else {
            return $this->moveNode($target, $target->rgt + 1, 0);
        }
    }

    public function getIsLeaf()
    {
        return $this->rgt - $this->lft === 1;
    }

    public function getIsRoot()
    {
        return $this->lft == 1;
    }

    public function isDescendantOf($subj)
    {
        return ($this->lft > $subj->lft) && ($this->rgt < $subj->rgt) && ($this->root === $subj->root);
    }
    
    public function makeRoot()
    {
        if ($this->getIsRoot()) {
            throw new Exception('The node already is root node.');
        }

        if ($this->getIsNew()) {
            $this->lft = 1;
            $this->rgt = 2;
            $this->depth = 1;
            $this->root = $this->getNextRoot();
        } else {
            $left = $this->lft;
            $right = $this->rgt;
            $depthDelta = 1 - $this->depth;
            $delta = 1 - $left;
            $this->objects()
                ->filter([
                    'lft__gte' => $left,
                    'rgt__lte' => $right,
                    'root' => $this->root
                ])
                ->update([
                    'lft' => new Expression('lft' . sprintf('%+d', $delta)),
                    'rgt' => new Expression('rgt' . sprintf('%+d', $delta)),
                    'depth' => new Expression('depth' . sprintf('%+d', $depthDelta)),
                    'root' => $this->getNextRoot()
                ]);
            $this->shiftLeftRight($right + 1, $left - $right - 1);
        }

        return $this;
    }

    protected function shiftLeftRight($key, $delta)
    {
        foreach (['lft', 'rgt'] as $attribute) {
            $this->objects()
                ->filter([$attribute . '__gte' => $key, 'root' => $this->root])
                ->update([$attribute => new Expression($attribute . sprintf('%+d', $delta))]);
        }
    }

    protected function getNextRoot()
    {
        return $this->objects()->max('root') + 1;
    }

    /**
     * @param TreeModel $target
     * @param $key
     * @param $depthUp
     * @return $this
     * @throws Exception
     */
    protected function addNode($target, $key, $depthUp)
    {
        if (!$this->getIsNew()) {
            throw new Exception('The node can\'t be inserted because it is not new.');
        }

        if ($this->pk == $target->pk) {
            throw new Exception('The target node should not be self.');
        }

        if (!$depthUp && $target->getIsRoot()) {
            throw new Exception('The target node should not be root.');
        }

        $this->root = $target->root;

        $this->shiftLeftRight($key, 2);
        $this->lft = $key;
        $this->rgt = $key + 1;
        $this->depth = $target->depth + $depthUp;
        return $this;
    }

    /**
     * @param TreeModel $target
     * @param $key
     * @param $depthUp
     * @return bool
     * @throws Exception
     */
    private function moveNode($target, $key, $depthUp)
    {
        if ($this->getIsNew()) {
            throw new Exception('The node should not be new record.');
        }

        if ($this->pk == $target->pk) {
            throw new Exception('The target node should not be self.');
        }

        if ($target->isDescendantOf($this)) {
            throw new Exception('The target node should not be descendant.');
        }

        if (!$depthUp && $target->getIsRoot()) {
            throw new Exception('The target node should not be root.');
        }

        $left = $this->lft;
        $right = $this->rgt;
        $depthDelta = $target->depth - $this->depth + $depthUp;

        if ($this->root !== $target->root) {
            foreach (['lft', 'rgt'] as $attribute) {
                $this->objects()
                    ->filter([$attribute . '__gte' => $key, 'root' => $target->root])
                    ->update([$attribute => new Expression($attribute . sprintf('%+d', $right - $left + 1))]);
            }

            $delta = $key - $left;
            $this->objects()
                ->filter(['lft__gte' => $left, 'rgt__lte' => $right, 'root' => $this->root])
                ->update([
                    'lft' => new Expression('lft' . sprintf('%+d', $delta)),
                    'rgt' => new Expression('rgt' . sprintf('%+d', $delta)),
                    'depth' => new Expression('depth' . sprintf('%+d', $depthDelta)),
                    'root' => $target->root,
                ]);

            $this->shiftLeftRight($right + 1, $left - $right - 1);

        } else {
            $delta = $right - $left + 1;
            $this->shiftLeftRight($key, $delta);

            if ($left >= $key) {
                $left += $delta;
                $right += $delta;
            }

            $this->objects()
                ->filter(['lft__gte' => $left, 'rgt__lte' => $right, 'root' => $this->root])
                ->update([
                    'depth' => new Expression('depth' . sprintf('%+d', $depthDelta))
                ]);

            foreach (['lft', 'rgt'] as $attribute) {
                $this->objects()
                    ->filter([$attribute . '__gte' => $left, $attribute . '__lte' => $right, 'root' => $this->root])
                    ->update([$attribute => new Expression($attribute . sprintf('%+d', $key - $left))]);
            }

            $this->shiftLeftRight($right + 1, -$delta);
        }
        return true;
    }

    public function delete()
    {
        if ($this->getIsLeaf()) {
            $result = parent::delete();
        } else {
            $result = $this->objects()->filter([
                'lft__gte' => $this->lft,
                'rgt__lte' => $this->rgt,
                'root' => $this->root
            ])->delete();
        }
        return (bool)$result;
    }
}