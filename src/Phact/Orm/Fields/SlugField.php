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
use Phact\Orm\QuerySet;

class SlugField extends CharField
{
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

    public function init()
    {
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

    public function getIsRequired()
    {
        return false;
    }

    public function beforeSave()
    {
        if (!$this->blank && !$this->null && !$this->attribute) {
            $model = $this->getModel();
            $source = $model->getFieldValue($this->source);
            $slug = $this->_slugify->slugify($source);
            if ($this->unique) {
                $slug = $this->unique($slug, 0);
            }
            $this->setValue($slug);
        }
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