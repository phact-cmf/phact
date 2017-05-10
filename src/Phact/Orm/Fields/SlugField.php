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

use Cocur\Slugify\Slugify;
use Phact\Orm\Expression;
use Phact\Orm\QuerySet;
use Phact\Orm\TreeModel;

class SlugField extends CharField
{
    /**
     * Tree
     * @var bool
     */
    public $tree;

    /**
     * Unique value
     * @var bool
     */
    public $unique = true;

    /**
     * Rulesets for Slugify
     * @var array
     */
    public $rulesets = ['russian', 'default'];

    /**
     * Lowercase value
     * @var bool
     */
    public $lowercase = true;

    /**
     * Regex for Slugify
     * @var null
     */
    public $regexp = null;

    /**
     * Words separator
     * @var string
     */
    public $separator = '-';

    /**
     * Source field in model
     * @var string
     */
    public $source = 'name';

    /**
     * @var Slugify
     */
    protected $_slugify = null;

    public function getSlugify()
    {
        if (!$this->_slugify) {
            $attributes = [
                'rulesets' => $this->rulesets,
                'lowercase' => $this->lowercase,
                'separator' => $this->separator
            ];
            if ($this->regexp) {
                $attributes['regexp'] = $this->regexp;
            }
            $this->_slugify = new Slugify($attributes);
        }
        return $this->_slugify;
    }

    public function setOwnerModelClass($modelClass)
    {
        if (is_null($this->tree) && is_a($modelClass, TreeModel::class, true)) {
            $this->tree = true;
        }
        parent::setOwnerModelClass($modelClass);
    }

    public function getIsRequired()
    {
        return false;
    }

    public function beforeSave()
    {
        $model = $this->getModel();
        if ($this->tree && !$model->getIsNew()) {
            $slug = $this->attribute ?: $this->buildSlug();
            $oldSlug = $model->getOldAttribute($this->name);
            if ($oldSlug && $slug != $oldSlug) {
                $model->objects()->filter([
                    'lft__gt' => $model->getOldAttribute('lft'),
                    'rgt__lt' => $model->getOldAttribute('rgt'),
                    'root' => $model->getOldAttribute('root')
                ])->update([
                    $this->name => new Expression("REPLACE({" . $this->name . "}, ?, ?)", [$oldSlug, $slug])
                ]);
                $this->setValue($slug);
            }
        } else {
            if (!$this->blank && !$this->null && !$this->attribute) {
                $slug = $this->buildSlug();
                $this->setValue($slug);
            }
        }
    }

    public function buildSlug()
    {
        $model = $this->getModel();
        $source = $model->getFieldValue($this->source);
        $slug = $this->getSlugify()->slugify($source);
        if ($this->tree && $model->parent) {
            /** @var TreeModel $model */
            $slug = $model->parent->{$this->name} . '/' . $slug;
        }
        if ($this->unique) {
            $slug = $this->unique($slug, 0);
        }
        return $slug;
    }

    public function unique($rawUrl, $counter)
    {
        $model = $this->getModel();
        $url = $rawUrl;
        if ($counter > 0) {
            $url .= $this->separator . $counter;
        }
        /** @var QuerySet $qs */
        $qs = $model::objects()->filter([$this->getName() => $url]);
        if ($pk = $model->pk) {
            $qs = $qs->exclude(['pk' => $pk]);
        }
        if ($qs->count() > 0) {
            $counter++;
            return $this->unique($rawUrl, $counter);
        }
        return $url;
    }
}